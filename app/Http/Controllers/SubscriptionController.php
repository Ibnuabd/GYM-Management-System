<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Status langganan yang masih boleh dibatalkan.
     *
     * @var list<string>
     */
    private const CANCELLABLE_STATUSES = ['active', 'queued'];

    /**
     * Store a new subscription for the specified member.
     */
    public function store(StoreSubscriptionRequest $request, Member $member): RedirectResponse
    {
        $validated = $request->validated();
        $package = Package::findOrFail($validated['package_id']);

        DB::transaction(function () use ($validated, $package, $member) {
            $queueAfterActive = ! empty($validated['queue_after_active']);

            if ($queueAfterActive) {
                $latestSub = $member->subscriptions()
                    ->whereIn('status', ['active', 'queued'])
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->orderBy('end_date', 'desc')
                    ->first();

                if ($latestSub) {
                    $startDate = Carbon::parse($latestSub->end_date)->addDay();
                    $status = 'queued';
                } else {
                    $startDate = Carbon::today();
                    $status = 'active';
                }
            } elseif (! empty($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $status = $startDate->isFuture() ? 'queued' : 'active';
            } else {
                $startDate = Carbon::today();
                $status = 'active';
            }

            // Automated end_date calculation based on package duration
            $endDate = $startDate->copy()->addDays($package->duration_days - 1);

            // Allocation of PT sessions for PT or combo packages
            $totalSessions = in_array($package->type, ['pt', 'combo']) ? $package->session_count : 0;
            $usedSessions = 0;

            $subscription = MemberSubscription::create([
                'member_id' => $member->id,
                'package_id' => $package->id,
                'pt_user_id' => in_array($package->type, ['pt', 'combo']) ? ($validated['pt_user_id'] ?? null) : null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_sessions' => $totalSessions,
                'used_sessions' => $usedSessions,
                'price_paid' => $validated['price_paid'] ?? $package->price,
                'status' => $status,
                'notes' => $validated['notes'] ?? null,
            ]);

            ActivityLog::record('subscription.created', "Menambahkan paket {$package->name} untuk member {$member->full_name}", $subscription, [
                'package' => $package->name,
                'price_paid' => (float) $subscription->price_paid,
                'start_date' => $subscription->start_date->toDateString(),
                'end_date' => $subscription->end_date->toDateString(),
                'status' => $status,
            ]);
        });

        return redirect()->route('members.show', $member)
            ->with('success', "Paket {$package->name} berhasil ditambahkan ke member {$member->full_name}!");
    }

    /**
     * Cancel the specified subscription.
     */
    public function cancel(MemberSubscription $subscription): RedirectResponse
    {
        // Hasilnya null jika berhasil, atau berisi pesan error jika ditolak.
        $errorMessage = DB::transaction(function () use ($subscription) {
            // Ambil ulang data sambil MENGUNCI barisnya, supaya status yang dicek
            // tidak berubah oleh request lain di saat yang sama.
            $lockedSubscription = MemberSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            // Aturan: hanya langganan "active" atau "queued" yang boleh dibatalkan.
            if (! in_array($lockedSubscription->status, self::CANCELLABLE_STATUSES, true)) {
                return "Langganan berstatus \"{$lockedSubscription->status}\" tidak dapat dibatalkan. Hanya langganan aktif atau antrean (queued) yang bisa dibatalkan.";
            }

            $lockedSubscription->update(['status' => 'cancelled']);

            ActivityLog::record(
                'subscription.cancelled',
                "Membatalkan langganan {$lockedSubscription->package->name} milik {$lockedSubscription->member->full_name}",
                $lockedSubscription,
                ActivityLog::changesOf($lockedSubscription),
            );

            return null;
        });

        if ($errorMessage !== null) {
            return redirect()->route('members.show', $subscription->member_id)->with('error', $errorMessage);
        }

        return redirect()->route('members.show', $subscription->member_id)
            ->with('success', 'Status langganan berhasil dibatalkan.');
    }

    /**
     * Manually activate a queued subscription.
     *
     * Hanya langganan "queued" yang boleh diaktifkan. Tanpa aturan ini, langganan yang sudah
     * dibatalkan, habis, atau kedaluwarsa bisa "dihidupkan lagi" dengan tanggal baru (gratis).
     */
    public function activate(MemberSubscription $subscription): RedirectResponse
    {
        // Hasilnya null jika berhasil, atau berisi pesan error jika ditolak.
        $errorMessage = DB::transaction(function () use ($subscription) {
            // Ambil ulang data sambil MENGUNCI barisnya (lihat penjelasan di cancel()).
            $lockedSubscription = MemberSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();

            // Aturan: hanya langganan "queued" yang boleh diaktifkan.
            if ($lockedSubscription->status !== 'queued') {
                return "Langganan berstatus \"{$lockedSubscription->status}\" tidak dapat diaktifkan. Hanya langganan antrean (queued) yang bisa diaktifkan.";
            }

            // Masa aktif dihitung ulang mulai hari ini sesuai durasi paket.
            $startDate = Carbon::today();
            $endDate = $startDate->copy()->addDays($lockedSubscription->package->duration_days - 1);

            $lockedSubscription->update([
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'active',
            ]);

            ActivityLog::record(
                'subscription.activated',
                "Mengaktifkan langganan {$lockedSubscription->package->name} milik {$lockedSubscription->member->full_name}",
                $lockedSubscription,
                ActivityLog::changesOf($lockedSubscription),
            );

            return null;
        });

        if ($errorMessage !== null) {
            return redirect()->route('members.show', $subscription->member_id)->with('error', $errorMessage);
        }

        return redirect()->route('members.show', $subscription->member_id)
            ->with('success', 'Langganan berhasil diaktifkan per hari ini.');
    }
}
