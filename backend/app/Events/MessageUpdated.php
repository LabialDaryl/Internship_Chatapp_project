<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender', 'parent']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'parent_id' => $this->message->parent_id,
            'parent' => $this->message->parent ? [
                'id' => $this->message->parent->id,
                'body' => $this->message->parent->body,
                'type' => $this->message->parent->type,
                'sender' => [
                    'id' => $this->message->parent->sender->id ?? null,
                    'name' => $this->message->parent->sender->name ?? null,
                ]
            ] : null,
            'sender_id' => $this->message->sender_id,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'username' => $this->message->sender->username,
                'avatar_url' => $this->message->sender->avatar_url,
            ],
            'body' => $this->message->body,
            'type' => $this->message->type,
            'is_edited' => $this->message->is_edited,
            'is_deleted' => $this->message->is_deleted,
            'created_at' => $this->message->created_at->toISOString(),
            'updated_at' => $this->message->updated_at->toISOString(),
        ];
    }
}
