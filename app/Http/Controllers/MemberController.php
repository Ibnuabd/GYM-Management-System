<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use App\Rules\Base64Image;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a paginated listing of members with search and status filtering.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Member::query()
            ->with(['subscriptions.package', 'subscriptions.personalTrainer'])
            ->search($search);

        if ($status && in_array($status, ['active', 'inactive', 'suspended'])) {
            $query->where('status', $status);
        }

        $members = $query->latest('id')->paginate(12)->withQueryString();

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? 'all',
            ],
            'stats' => [
                'total' => Member::count(),
                'active' => Member::where('status', 'active')->count(),
                'inactive' => Member::where('status', 'inactive')->count(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new member.
     */
    public function create(): Response
    {
        $packages = Package::where('is_active', true)->orderBy('type')->get();
        $personalTrainers = User::role('pt')->get(['id', 'name', 'email']);

        return Inertia::render('Members/Create', [
            'packages' => $packages,
            'personalTrainers' => $personalTrainers,
            'suggestedMemberCode' => Member::generateMemberCode(),
        ]);
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $photoPath = $this->handlePhotoUpload($request);

        $member = DB::transaction(function () use ($validated, $photoPath) {
            $member = Member::create([
                'member_code' => Member::generateMemberCode(),
                'nik' => $validated['nik'] ?? null,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'rfid_uid' => filled($validated['rfid_uid'] ?? null) ? strtoupper(trim($validated['rfid_uid'])) : null,
                'photo_url' => $photoPath,
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => 'active',
            ]);

            // Optional initial subscription
            if (! empty($validated['package_id'])) {
                $package = Package::findOrFail($validated['package_id']);
                $startDate = ! empty($validated['start_date'])
                    ? Carbon::parse($validated['start_date'])
                    : Carbon::today();

                $endDate = $startDate->copy()->addDays($package->duration_days - 1);
                $status = $startDate->isFuture() ? 'queued' : 'active';

                MemberSubscription::create([
                    'member_id' => $member->id,
                    'package_id' => $package->id,
                    'pt_user_id' => in_array($package->type, ['pt', 'combo']) ? ($validated['pt_user_id'] ?? null) : null,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_sessions' => $package->session_count,
                    'used_sessions' => 0,
                    'price_paid' => $package->price,
                    'status' => $status,
                    'notes' => $validated['notes'] ?? 'Langganan perdana saat pendaftaran member baru.',
                ]);
            }

            ActivityLog::record('member.created', "Mendaftarkan member baru {$member->full_name} ({$member->member_code})", $member);

            return $member;
        });

        return redirect()->route('members.show', $member)
            ->with('success', "Member {$member->full_name} ({$member->member_code}) berhasil didaftarkan!");
    }

    /**
     * Display the specified member profile with active subscriptions and PT session balances.
     */
    public function show(Member $member): Response
    {
        $member->load([
            'subscriptions.package',
            'subscriptions.personalTrainer',
        ]);

        $packages = Package::where('is_active', true)->orderBy('type')->get();
        $personalTrainers = User::role('pt')->get(['id', 'name', 'email']);

        $latestActiveGymSub = $member->subscriptions
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::today())
            ->sortByDesc('end_date')
            ->first();

        $suggestedQueueDate = $latestActiveGymSub
            ? Carbon::parse($latestActiveGymSub->end_date)->addDay()->toDateString()
            : Carbon::today()->toDateString();

        return Inertia::render('Members/Show', [
            'member' => $member,
            'packages' => $packages,
            'personalTrainers' => $personalTrainers,
            'suggestedQueueDate' => $suggestedQueueDate,
            // Hanya kirim "sudah punya password atau belum", JANGAN kirim password-nya.
            'hasPortalPassword' => filled($member->password),
        ]);
    }

    /**
     * Show the form for editing the specified member.
     */
    public function edit(Member $member): Response
    {
        return Inertia::render('Members/Edit', [
            'member' => $member,
        ]);
    }

    /**
     * Update the specified member in storage.
     */
    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $validated = $request->validated();

        $photoPath = $this->handlePhotoUpload($request);
        if ($photoPath) {
            if ($member->photo_url && ! str_starts_with($member->photo_url, 'http')) {
                Storage::disk('public')->delete($member->photo_url);
            }
            $member->photo_url = $photoPath;
        }

        $member->update([
            'nik' => $validated['nik'] ?? null,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'rfid_uid' => filled($validated['rfid_uid'] ?? null) ? strtoupper(trim($validated['rfid_uid'])) : null,
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        ActivityLog::record(
            'member.updated',
            "Mengubah data member {$member->full_name} ({$member->member_code})",
            $member,
            ActivityLog::changesOf($member),
        );

        return redirect()->route('members.show', $member)
            ->with('success', "Data profil member {$member->full_name} berhasil diperbarui.");
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Member $member): RedirectResponse
    {
        if ($member->photo_url && ! str_starts_with($member->photo_url, 'http')) {
            Storage::disk('public')->delete($member->photo_url);
        }

        $name = $member->full_name;
        $member->delete();

        ActivityLog::record('member.deleted', "Menghapus member {$name} ({$member->member_code})", $member, [
            'full_name' => $name,
            'member_code' => $member->member_code,
        ]);

        return redirect()->route('members.index')
            ->with('success', "Member {$name} berhasil dihapus dari sistem.");
    }

    /**
     * Fast debounced search endpoint (< 300ms) for instant lookups by name, phone, or RFID card scan.
     */
    public function quickSearch(Request $request): JsonResponse
    {
        $term = $request->input('q') ?? $request->input('search');

        if (blank($term)) {
            return response()->json([]);
        }

        $members = Member::query()
            ->search($term)
            ->with(['subscriptions.package'])
            ->limit(10)
            ->get()
            ->map(function (Member $m) {
                return [
                    'id' => $m->id,
                    'member_code' => $m->member_code,
                    'full_name' => $m->full_name,
                    'phone' => $m->phone,
                    'email' => $m->email,
                    'rfid_uid' => $m->rfid_uid,
                    'photo_asset_url' => $m->photo_asset_url,
                    'status' => $m->status,
                    'has_active_subscription' => $m->has_active_subscription,
                    'remaining_validity_days' => $m->remaining_validity_days,
                    'pt_session_balance' => $m->pt_session_balance,
                    'show_url' => route('members.show', $m),
                ];
            });

        return response()->json($members);
    }

    /**
     * Check if RFID card UID is already registered or unique.
     */
    public function checkRfid(Request $request): JsonResponse
    {
        $rfid = trim((string) $request->input('rfid'));
        $excludeMemberId = $request->input('exclude_member_id');

        if (empty($rfid)) {
            return response()->json(['valid' => false, 'message' => 'RFID UID tidak boleh kosong.']);
        }

        $query = Member::where('rfid_uid', strtoupper($rfid));
        if ($excludeMemberId) {
            $query->where('id', '!=', $excludeMemberId);
        }

        $existing = $query->first(['id', 'member_code', 'full_name', 'phone']);

        if ($existing) {
            return response()->json([
                'unique' => false,
                'message' => "Kartu RFID ini sudah dipakai oleh {$existing->full_name} ({$existing->member_code}).",
                'member' => $existing,
            ]);
        }

        return response()->json([
            'unique' => true,
            'message' => 'Kartu RFID siap digunakan.',
        ]);
    }

    /**
     * Simpan foto member, baik dari upload file biasa maupun dari jepretan webcam (base64).
     *
     * Mengembalikan path file yang tersimpan, atau null jika tidak ada foto.
     */
    protected function handlePhotoUpload(Request $request): ?string
    {
        $folder = 'members/'.date('Y/m');

        // Cara 1: upload file biasa. Sudah divalidasi "image|mimes:jpeg,png,jpg,webp",
        // dan Laravel memberi nama acak dengan ekstensi sesuai isi file.
        if ($request->hasFile('photo')) {
            return $request->file('photo')->store($folder, 'public');
        }

        // Cara 2: foto webcam berupa teks base64.
        if ($request->filled('photo_data')) {
            // decode() memeriksa isi file dan menentukan ekstensi yang aman (jpg/png/webp).
            // Teks ini sudah divalidasi oleh aturan Base64Image di Form Request,
            // tapi kita tetap cek null sebagai pengaman tambahan.
            $image = Base64Image::decode($request->input('photo_data'));
            if ($image === null) {
                return null;
            }

            $fileName = $folder.'/'.Str::uuid().'.'.$image['extension'];
            Storage::disk('public')->put($fileName, $image['contents']);

            return $fileName;
        }

        return null;
    }
}
