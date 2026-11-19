<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Making a product's 3D model from its photo. The owner looks at the result and approves it before customers see it. */
class Product3dJob extends Model
{
    protected $table = 'product_3d_jobs';

    protected $fillable = ['product_id', 'user_id', 'provider', 'provider_task_id', 'status', 'progress', 'result_path', 'error'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isWorking(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
