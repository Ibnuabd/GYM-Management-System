<?php

namespace App\Models;

use Database\Factories\GymClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['title', 'description', 'capacity', 'instructor_id', 'start_time', 'end_time', 'is_active'])]
#[Hidden(['created_at', 'updated_at'])]
class GymClass extends Model
{
    /** @use HasFactory<GymClassFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'available_spots',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    /**
     * Instructor relationship.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Participants (enrolled users) relationship.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_user')->withTimestamps();
    }

    /**
     * Available spots accessor.
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, (int) $this->capacity - $this->participants()->count());
    }
}
