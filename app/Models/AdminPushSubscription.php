<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPushSubscription extends Model
{
    protected $guarded = [];
    protected $hidden = ['endpoint', 'public_key', 'auth_token', 'endpoint_hash'];
    protected $casts = ['last_seen_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
