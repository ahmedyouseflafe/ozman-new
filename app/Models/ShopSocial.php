<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShopSocial extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'facebook',
        'instagram',
        'tiktok',
        'telegram',
        'snapchat',
        'twitter',
        'youtube',
        'whatsapp',
    ];

    // العلاقات

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
