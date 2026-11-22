<?php

namespace App\Models;

use App\Notifications\ApointResetPassword;
use App\Notifications\ApointVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        try {
            $this->notify(new ApointVerifyEmail);
        } catch (Throwable $exception) {
            Log::error('Apoint verification email could not be delivered.', [
                'user_id' => $this->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ApointResetPassword($token));
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
