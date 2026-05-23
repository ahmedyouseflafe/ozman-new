<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainScreen extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'media',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
