<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $member_id
 * @property string $rfid_uid
 * @property string $status
 * @property string|null $snapshot_path
 * @property int|null $member_subscription_id
 * @property \Illuminate\Support\Carbon|null $scanned_at
 * @property-read string|null $snapshot_url
 * @property-read string|null $scanned_time
 * @property-read string|null $time_ago
 * @property-read Member|null $member
 * @property-read MemberSubscription|null $subscription
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_id',
        'rfid_uid',
        'status',
        'snapshot_path',
        'member_subscription_id',
        'scanned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'snapshot_url',
        'scanned_time',
        'time_ago',
    ];

    /**
     * Get the member who scanned attendance.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the subscription associated with this attendance log.
     *
     * @return BelongsTo<MemberSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }

    /**
     * Scope query to attendances of today.
     *
     * @param  Builder<Attendance>  $query
     * @return Builder<Attendance>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scanned_at', Carbon::today());
    }

    /**
     * Get the full URL for the webcam snapshot.
     *
     * @return Attribute<string|null, never>
     */
    protected function snapshotUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->snapshot_path) {
                    return null;
                }

                if (str_starts_with($this->snapshot_path, 'http://') || str_starts_with($this->snapshot_path, 'https://')) {
                    return $this->snapshot_path;
                }

                return Storage::disk('public')->url($this->snapshot_path);
            }
        );
    }

    /**
     * Formatted scan time in H:i:s.
     *
     * @return Attribute<string|null, never>
     */
    protected function scannedTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scanned_at?->format('H:i:s')
        );
    }

    /**
     * Relative human-readable time (e.g. '1 menit lalu').
     *
     * @return Attribute<string|null, never>
     */
    protected function timeAgo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scanned_at?->diffForHumans()
        );
    }
}
