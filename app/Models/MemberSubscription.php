<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MemberSubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberSubscription extends Model
{
    /** @use HasFactory<MemberSubscriptionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'package_id',
        'pt_user_id',
        'start_date',
        'end_date',
        'total_sessions',
        'used_sessions',
        'price_paid',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_sessions' => 'integer',
        'used_sessions' => 'integer',
        'price_paid' => 'decimal:2',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'remaining_sessions',
        'remaining_days',
    ];

    /**
     * Get the member this subscription belongs to.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the package subscribed to.
     *
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the personal trainer assigned to this subscription.
     *
     * @return BelongsTo<User, $this>
     */
    public function personalTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pt_user_id');
    }

    /**
     * Get all PT session logs for this subscription.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function ptSessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class, 'member_subscription_id')->orderByDesc('session_date');
    }

    /**
     * Hanya langganan yang dihitung sebagai PENDAPATAN (semua status kecuali "cancelled").
     *
     * Langganan yang dibatalkan dianggap tidak menghasilkan uang, jadi tidak ikut laporan.
     * Pemakaian: MemberSubscription::countedAsRevenue()->sum('price_paid')
     *
     * @param  Builder<MemberSubscription>  $query
     * @return Builder<MemberSubscription>
     */
    public function scopeCountedAsRevenue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /**
     * Ubah langganan "queued" menjadi "active" jika tanggal mulainya sudah tiba.
     *
     * Contoh: member membeli perpanjangan yang mulai 1 Desember (status "queued").
     * Pada 1 Desember, langganan itu harus otomatis menjadi "active".
     *
     * Dipakai oleh command "subscriptions:sync-status" (semua member)
     * dan oleh kiosk absensi (satu member, lewat $memberId).
     *
     * @return int Jumlah langganan yang diaktifkan.
     */
    public static function activateQueuedThatHaveStarted(?int $memberId = null): int
    {
        return static::query()
            ->where('status', 'queued')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->when($memberId !== null, fn ($query) => $query->where('member_id', $memberId))
            ->update(['status' => 'active']);
    }

    /**
     * Ubah langganan "active" menjadi "expired" jika tanggal berakhirnya sudah lewat.
     *
     * Contoh: paket berakhir 30 November. Mulai 1 Desember statusnya menjadi "expired".
     *
     * @return int Jumlah langganan yang dikedaluwarsakan.
     */
    public static function expireActiveThatHaveEnded(): int
    {
        return static::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'expired']);
    }

    /**
     * Remaining PT sessions count.
     *
     * @return Attribute<int, never>
     */
    protected function remainingSessions(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->total_sessions - $this->used_sessions)
        );
    }

    /**
     * Remaining validity days until subscription ends.
     *
     * @return Attribute<int, never>
     */
    protected function remainingDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->end_date) {
                    return 0;
                }

                $today = Carbon::today();
                if ($this->end_date < $today) {
                    return 0;
                }

                return (int) $today->diffInDays($this->end_date, false);
            }
        );
    }
}
