<?php
namespace App\Models;
class PromoterUpdate extends \Illuminate\Database\Eloquent\Model {
    protected $guarded = [];
    public function post() { return $this->belongsTo(PromoterPost::class, 'promoter_post_id'); }
}
