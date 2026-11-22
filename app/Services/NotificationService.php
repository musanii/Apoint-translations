<?php

namespace App\\Services;

use App\\Models\\User;
use App\\Notifications\\ApointNotification;
use Illuminate\\Support\\Facades\\DB;

class NotificationService
{
    public function admins(string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(function () use ($title, $message, $url, $category, $email): void {
            User::query()->where('is_admin', true)->each(
                fn (User $admin) => $admin->notify(new ApointNotification($title, $message, $url, $category, $email))
            );
        });
    }

    public function user(User $user, string $title, string $message, string $url, string $category, bool $email = false): void
    {
        DB::afterCommit(fn () => $user->notify(new ApointNotification($title, $message, $url, $category, $email)));
    }
}
