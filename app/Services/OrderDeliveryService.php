<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDeliveryDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderDeliveryService
{
    public function __construct(private readonly OrderStatusService $statuses) {}

    /**
     * Store the final files privately, then complete the digital order.
     *
     * @param array<int, UploadedFile> $files
     * @return array<int, OrderDeliveryDocument>
     */
    public function deliverDigitalOrder(Order $order, array $files, User $admin): array
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($order, $files, $admin, &$storedPaths): array {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->status !== Order::STATUS_IN_PROGRESS) {
                    throw ValidationException::withMessages([
                        'documents' => 'Only orders currently being processed can receive final documents.',
                    ]);
                }

                if ($lockedOrder->delivery_method !== 'digital') {
                    throw ValidationException::withMessages([
                        'documents' => 'This order was not set up for online document delivery.',
                    ]);
                }

                if ($files === [] || count($files) > (int) config('apoint.documents.max_files')) {
                    throw ValidationException::withMessages([
                        'documents' => 'Upload between 1 and '.config('apoint.documents.max_files').' final documents.',
                    ]);
                }

                $documents = [];

                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $mimeType = $file->getMimeType();

                    $storedPath = $file->store("order-deliveries/{$lockedOrder->id}", 'local');

                    if (! $storedPath) {
                        throw new \RuntimeException('A completed document could not be stored.');
                    }

                    $storedPaths[] = $storedPath;

                    $documents[] = $lockedOrder->deliveryDocuments()->create([
                        'uploaded_by' => $admin->id,
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'stored_name' => basename($storedPath),
                        'disk' => 'local',
                        'path' => $storedPath,
                        'mime_type' => $mimeType,
                        'extension' => $extension,
                        'size' => $file->getSize(),
                    ]);
                }

                $this->statuses->complete($lockedOrder);

                return $documents;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
