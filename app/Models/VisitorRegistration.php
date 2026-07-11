<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'distributor_id',
        'distributor_marketer_id',
        'marketing_source',
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

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }

    public function distributorMarketer()
    {
        return $this->belongsTo(DistributorMarketer::class);
    }
}
