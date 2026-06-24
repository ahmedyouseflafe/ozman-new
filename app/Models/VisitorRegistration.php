<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'phone',
        'shop_name',
        'tax_file',
        'business_location',
        'residence_address',
        'latitude',
        'longitude',
        'map_link',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
}
