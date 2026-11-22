<?php

namespace App\\Services;

use App\\Models\\User;
use App\\Notifications\\ApointNotification;

class NotificationService
{
    public function admins(
        string $title,
        string $message,
        string $url,
        string $category,
        bool $email = false
    ): void {
        User::query()
            ->where('is_admin', true)
            ->each(fn (User $admin) => $admin->notify(
                new ApointNotification($title, $message, $url, $category, $email)
            ));
    }

    public function user(
        User $user,
        string $title,
        string $message,
        string $url,
        string $category,
        bool $email = false
    ): void {
        $user->notify(new ApointNotification($title, $message, $url, $category, $email));
    }
}
