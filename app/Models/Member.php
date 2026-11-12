<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_code',
        'nik',
        'full_name',
        'phone',
        'email',
        'password',
        'rfid_uid',
        'photo_url',
        'gender',
        'birth_date',
        'address',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'photo_asset_url',
        'remaining_validity_days',
        'pt_session_balance',
        'has_active_subscription',
    ];

    /**
     * Get all subscriptions for this member.
     *
     * @return HasMany<MemberSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class)->latest('start_date');
    }

    /**
     * Get active subscriptions for this member.
     *
     * @return HasMany<MemberSubscription, $this>
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', Carbon::today());
    }

    /**
     * Get attendance records for this member.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('scanned_at');
    }

    /**
     * Get all PT workout session logs recorded for this member.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function ptSessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class)->orderByDesc('session_date');
    }

    /**
     * Get packages associated through member_subscriptions.
     *
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'member_subscriptions')
            ->withPivot([
                'id',
                'pt_user_id',
                'start_date',
                'end_date',
                'total_sessions',
                'used_sessions',
                'price_paid',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Pesan untuk member yang tidak boleh memakai portal karena statusnya bukan "active".
     * Dipakai saat login portal dan oleh middleware EnsureMemberIsActive.
     */
    public static function portalAccessDeniedMessage(string $status): string
    {
        if ($status === 'suspended') {
            return 'Akun keanggotaan Anda sedang ditangguhkan. Silakan hubungi Customer Service kami.';
        }

        return 'Akun keanggotaan Anda sedang tidak aktif. Silakan hubungi Customer Service kami.';
    }

    /**
     * Generate a unique sequential member code.
     */
    public static function generateMemberCode(): string
    {
        $prefix = 'MBR-'.date('Ym').'-';
        $latest = static::where('member_code', 'LIKE', $prefix.'%')
            ->orderBy('id', 'desc')
            ->value('member_code');

        if ($latest) {
            $number = (int) substr($latest, strlen($prefix)) + 1;
        } else {
            $number = 1;
        }

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope query to quickly search members by name, phone, RFID, member code, or NIK.
     *
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('full_name', 'LIKE', "%{$term}%")
                ->orWhere('phone', 'LIKE', "%{$term}%")
                ->orWhere('rfid_uid', 'LIKE', "%{$term}%")
                ->orWhere('member_code', 'LIKE', "%{$term}%")
                ->orWhere('nik', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Get photo asset URL.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoAssetUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->photo_url) {
                    return null;
                }

                if (str_starts_with($this->photo_url, 'http://') || str_starts_with($this->photo_url, 'https://')) {
                    return $this->photo_url;
                }

                return Storage::disk('public')->url($this->photo_url);
            }
        );
    }

    /**
     * Check if member currently has an active subscription.
     *
     * @return Attribute<bool, never>
     */
    protected function hasActiveSubscription(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('subscriptions')) {
                    return $this->subscriptions->contains(function (MemberSubscription $sub) {
                        return $sub->status === 'active' && $sub->end_date >= Carbon::today();
                    });
                }

                return $this->activeSubscriptions()->exists();
            }
        );
    }

    /**
     * Calculate remaining validity days from the latest active subscription.
     *
     * @return Attribute<int, never>
     */
    protected function remainingValidityDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                $subs = $this->relationLoaded('subscriptions')
                    ? $this->subscriptions
                    : $this->subscriptions()->get();

                $activeSubs = $subs->filter(function (MemberSubscription $sub) {
                    return $sub->status === 'active' && $sub->end_date >= Carbon::today();
                });

                if ($activeSubs->isEmpty()) {
                    return 0;
                }

                $maxEndDate = $activeSubs->max('end_date');

                return (int) max(0, Carbon::today()->diffInDays(Carbon::parse($maxEndDate), false));
            }
        );
    }

    /**
     * Calculate remaining PT sessions quota.
     *
     * @return Attribute<int, never>
     */
    protected function ptSessionBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                $subs = $this->relationLoaded('subscriptions')
                    ? $this->subscriptions
                    : $this->subscriptions()->get();

                $activePtSubs = $subs->filter(function (MemberSubscription $sub) {
                    return in_array($sub->status, ['active', 'queued']) && $sub->total_sessions > 0;
                });

                return (int) $activePtSubs->sum(function (MemberSubscription $sub) {
                    return max(0, $sub->total_sessions - $sub->used_sessions);
                });
            }
        );
    }
}
