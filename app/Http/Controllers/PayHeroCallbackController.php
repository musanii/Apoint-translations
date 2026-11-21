<?php

namespace App\Http\Controllers;

use App\Services\PayHeroCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PayHeroCallbackController extends Controller
{
    public function __construct(
        private readonly PayHeroCallbackService $callbackService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->callbackService->handle($request->all());
        } catch (Throwable $exception) {
            Log::error('PayHero callback processing failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Callback processing failed.',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Callback accepted.',
        ]);
    }
}
