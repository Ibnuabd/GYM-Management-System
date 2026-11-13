<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMemberPortalPasswordRequest;
use App\Models\ActivityLog;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;

/**
 * Staf mengatur / mereset password portal member.
 *
 * Member baru tidak punya password, jadi belum bisa login ke portal.
 * Setelah staf mengatur password di halaman profil member, member bisa login
 * ke /portal/login memakai email, nomor HP, atau kode member + password ini.
 */
class MemberPortalPasswordController extends Controller
{
    /**
     * Simpan password portal baru untuk member.
     */
    public function update(UpdateMemberPortalPasswordRequest $request, Member $member): RedirectResponse
    {
        // Password otomatis di-hash oleh cast 'password' => 'hashed' di model Member.
        $member->password = $request->validated('password');

        // Paksa logout dari "Remember me" di perangkat lain, karena passwordnya sudah diganti.
        $member->setRememberToken(null);

        $member->save();

        ActivityLog::record('member.portal_password_set', "Mengatur password portal member {$member->full_name} ({$member->member_code})", $member);

        return redirect()->route('members.show', $member)
            ->with('success', "Password portal untuk {$member->full_name} berhasil disimpan.");
    }
}
