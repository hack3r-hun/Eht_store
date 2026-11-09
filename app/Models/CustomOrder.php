<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomOrder extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'quoted' => 'Quoted',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'completed' => 'Completed',
    ];

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'item_type', 'colors', 'size', 'budget',
        'needed_by', 'description', 'reference_image', 'status', 'quote_amount', 'admin_notes',
    ];

    protected function casts(): array
    {
        return ['needed_by' => 'date', 'quote_amount' => 'decimal:2'];
    }

    public function getReferenceImageUrlAttribute(): ?string
    {
        return $this->reference_image ? Storage::disk('public')->url($this->reference_image) : null;
    }
}
