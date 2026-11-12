<?php

namespace App\Models;

use Database\Factories\PosVoidLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosVoidLog extends Model
{
    /** @use HasFactory<PosVoidLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'pos_transaction_id',
        'invoice_number',
        'cashier_user_id',
        'supervisor_user_id',
        'total_amount',
        'items_summary',
        'void_reason',
        'ip_address',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'items_summary' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the transaction that was voided.
     *
     * @return BelongsTo<PosTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    /**
     * Get the cashier who originally made the sale.
     *
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    /**
     * Get the supervisor who authorized this void.
     *
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
