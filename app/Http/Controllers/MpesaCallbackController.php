<?php

namespace App\Http\Controllers;

use App\Services\MpesaCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function __construct(private readonly MpesaCallbackService $callbackService) {}

    public function handle(Request $request): JsonResponse
    {

        try {
            $this->callbackService->handle($request->all());
        } catch (\Throwable $exception) {
            Log::error('M-Pesa callback processing failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Callback processing failed.',
            ], 500);

        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);

    }
}
