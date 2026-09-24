<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonAppointment extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shop_id', 'service', 'appointment_at', 'slot_key', 'customer_name',
        'customer_phone', 'notes', 'status', 'locale', 'whatsapp_opened_at',
    ];

    protected $casts = [
        'appointment_at' => 'datetime',
        'whatsapp_opened_at' => 'datetime',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => 'جديد',
            self::STATUS_CONFIRMED => 'مؤكد',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
