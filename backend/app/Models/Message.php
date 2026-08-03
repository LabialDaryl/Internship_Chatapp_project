<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'parent_id',
        'sender_id',
        'body',
        'type',
        'is_edited',
        'is_deleted',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id')->with('sender');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(ReadReceipt::class);
    }

    public function isReadBy(int $userId): bool
    {
        return $this->readReceipts()->where('user_id', $userId)->exists();
    }
}
