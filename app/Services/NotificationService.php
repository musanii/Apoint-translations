<?php

namespace App\Services;

use App\Mail\ApointNotificationMail;
use App\Models\User;
use App\Notifications\ApointNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public function admins(string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(function () use ($title, $message, $url, $category, $email): void {
            User::query()
                ->where('is_admin', true)
                ->orderBy('id')
                ->each(function (User $admin) use ($title, $message, $url, $category, $email): void {
                    $this->deliver($admin, $title, $message, $url, $category, $email);
                });
        });
    }

    public function user(User $user, string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(fn () => $this->deliver($user, $title, $message, $url, $category, $email));
    }

    private function deliver(User $user, string $title, string $message, string $url, string $category, bool $email): void
    {
        try {
            $user->notify(new ApointNotification($title, $message, $url, $category));
        } catch (Throwable $exception) {
            Log::error('Apoint in-app notification could not be stored.', [
                'user_id' => $user->id,
                'category' => $category,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        if (! $email || blank($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new ApointNotificationMail(
                recipientName: $user->name ?: 'there',
                title: $title,
                message: $message,
                actionUrl: $url,
                category: $category,
            ));
        } catch (Throwable $exception) {
            Log::error('Apoint notification email could not be queued.', [
                'user_id' => $user->id,
                'category' => $category,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
