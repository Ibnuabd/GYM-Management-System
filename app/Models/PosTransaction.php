<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\PosTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PosTransaction extends Model
{
    /** @use HasFactory<PosTransactionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'user_id',
        'member_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'payment_method',
        'cash_tendered',
        'change_due',
        'status',
        'notes',
        'voided_by',
        'void_reason',
        'voided_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    /**
     * Generate sequential invoice number for transactions.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'TRX-'.Carbon::now()->format('Ymd').'-';
        $latest = static::where('invoice_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('invoice_number');

        if ($latest) {
            $number = (int) substr($latest, strlen($prefix)) + 1;
        } else {
            $number = 1;
        }

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the cashier user who processed this transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the member if this transaction was linked to a registered member.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the supervisor who authorized voiding this transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Get all purchased items in this transaction.
     *
     * @return HasMany<PosTransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    /**
     * Get the void audit log if voided.
     *
     * @return HasOne<PosVoidLog, $this>
     */
    public function voidLog(): HasOne
    {
        return $this->hasOne(PosVoidLog::class);
    }

    /**
     * Scope query to transactions made today.
     *
     * @param  Builder<PosTransaction>  $query
     * @return Builder<PosTransaction>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope query to completed transactions.
     *
     * @param  Builder<PosTransaction>  $query
     * @return Builder<PosTransaction>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Scope query to voided transactions.
     *
     * @param  Builder<PosTransaction>  $query
     * @return Builder<PosTransaction>
     */
    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', 'VOID');
    }
}
