<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PayHeroApiClient
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(
            rtrim(config('services.payhero.base_url'), '/')
        )
            ->acceptJson()
            ->asJson()
            ->withBasicAuth(
                config('services.payhero.username'),
                config('services.payhero.password')
            )
            ->timeout(30);
    }

    public function initiateStkPush(array $payload): array
    {
        return $this->client()
            ->post('/api/v2/payments', $payload)
            ->throw()
            ->json();
    }

    public function getTransactionStatus(string $reference): array
    {
        return $this->client()
            ->get('api/v2/transaction-status', [
                'reference' => $reference,
            ])
            ->throw()
            ->json();
    }
}
