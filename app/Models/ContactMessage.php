<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'is_read',
        'last_replied_at',
        'last_replied_by',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'last_replied_at' => 'datetime',
        ];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->latest();
    }

    public function lastRepliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_replied_by');
    }

    public function getHasReplyAttribute(): bool
    {
        return $this->last_replied_at !== null;
    }
}
