<?php
namespace App\Models;
class CommunityPushSubscription extends \Illuminate\Database\Eloquent\Model {
    protected $guarded = [];
    protected $hidden = ['endpoint','public_key','auth_token','endpoint_hash'];
    public function user() { return $this->belongsTo(User::class); }
}
