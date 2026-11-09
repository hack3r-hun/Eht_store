<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['product_id', 'user_id', 'name', 'rating', 'title', 'body', 'is_verified', 'is_approved'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'is_approved' => 'boolean', 'rating' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
