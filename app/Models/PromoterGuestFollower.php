<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoterGuestFollower extends Model
{
    protected $fillable = ['promoter_profile_id', 'guest_token_hash'];

    protected $hidden = ['guest_token_hash'];

    public function profile()
    {
        return $this->belongsTo(PromoterProfile::class, 'promoter_profile_id');
    }
}
