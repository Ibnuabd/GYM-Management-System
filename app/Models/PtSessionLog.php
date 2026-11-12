<?php

namespace App\Models;

use Database\Factories\PtSessionLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtSessionLog extends Model
{
    /** @use HasFactory<PtSessionLogFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pt_session_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'member_subscription_id',
        'member_id',
        'pt_user_id',
        'session_date',
        'duration_minutes',
        'muscle_focus',
        'workout_notes',
        'member_feedback',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'session_date' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the subscription associated with this session log.
     *
     * @return BelongsTo<MemberSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MemberSubscription::class, 'member_subscription_id');
    }

    /**
     * Get the member who completed this workout session.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the Personal Trainer who conducted this session.
     *
     * @return BelongsTo<User, $this>
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pt_user_id');
    }
}
