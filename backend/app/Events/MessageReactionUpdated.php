<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public int $conversationId;
    public array $reactions;

    public function __construct(Message $message)
    {
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
        $this->reactions = $message->reactions()->with('user:id,name,username')->get()->toArray();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageReactionUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'reactions' => $this->reactions,
        ];
    }
}
