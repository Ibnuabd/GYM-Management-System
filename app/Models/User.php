<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'supervisor_pin'])]
#[Hidden(['password', 'supervisor_pin', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'supervisor_pin' => 'hashed',
        ];
    }

    /**
     * Verify supervisor authorization via PIN or primary password.
     */
    public function verifySupervisorPin(string $credential): bool
    {
        if ($this->supervisor_pin && Hash::check($credential, $this->supervisor_pin)) {
            return true;
        }

        return Hash::check($credential, $this->password);
    }

    /**
     * Get member subscriptions assigned to this trainer.
     *
     * @return HasMany<MemberSubscription, $this>
     */
    public function ptSubscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class, 'pt_user_id');
    }

    /**
     * Get workout session logs conducted by this trainer.
     *
     * @return HasMany<PtSessionLog, $this>
     */
    public function ptSessionLogs(): HasMany
    {
        return $this->hasMany(PtSessionLog::class, 'pt_user_id');
    }
}
