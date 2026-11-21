<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaAccessTokenService
{
    /**
     * Create a new class instance.
     */
    public function getAccessToken(): string
    {
        return Cache::remember(
            'mpesa_access_token',
            now()->addMinutes(55),
            function (): string {
                $baseUrl = config('services.mpesa.sandbox')
                ? 'https://sandbox.safaricom.co.ke'
                : 'https://api.safaricom.co.ke';

                $response = Http::withBasicAuth(
                    config('services.mpesa.consumer_key'),
                    config('services.mpesa.consumer_secret')
                )
                    ->acceptJson()
                    ->get(
                        $baseUrl.'/oauth/v1/generate',
                        ['grant_type' => 'client_credentials']
                    );

                $response->throw();

                $accessToken = $response->json('access_token');

                if (! is_string($accessToken) || $accessToken === '') {
                    throw new RuntimeException(
                        'Daraja did not return a valid access token.'
                    );
                }

                return $accessToken;
            }
        );
    }
}
