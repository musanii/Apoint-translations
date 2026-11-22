<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        if (! is_string($url) || ! $this->isSafeInternalUrl($request, $url)) {
            $url = $request->user()->is_admin ? route('admin.dashboard') : route('dashboard');
        }

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    private function isSafeInternalUrl(Request $request, string $url): bool
    {
        if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
            return true;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $host = $parts['host'] ?? null;
        $port = $parts['port'] ?? null;

        return is_string($host)
            && hash_equals($request->getHost(), $host)
            && ($port === null || $port === $request->getPort())
            && ! array_key_exists('user', $parts)
            && ! array_key_exists('pass', $parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true);
    }
}
