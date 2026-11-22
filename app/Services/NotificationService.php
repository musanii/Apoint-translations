<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ApointNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function admins(string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(function () use ($title, $message, $url, $category, $email): void {
            User::query()->where('is_admin', true)->each(function (User $admin) use ($title, $message, $url, $category, $email): void {
                try {
                    $admin->notify(new ApointNotification($title, $message, $url, $category, $email));
                } catch (Throwable $exception) {
                    Log::error('Apoint admin notification delivery failed.', [
                        'user_id' => $admin->id,
                        'category' => $category,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
        });
    }

    public function user(User $user, string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(function () use ($user, $title, $message, $url, $category, $email): void {
            try {
                $user->notify(new ApointNotification($title, $message, $url, $category, $email));
            } catch (Throwable $exception) {
                Log::error('Apoint user notification delivery failed.', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }
}
