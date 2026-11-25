<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeliveryDocument extends Model
{
    protected $fillable = [
        'uploaded_by',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
