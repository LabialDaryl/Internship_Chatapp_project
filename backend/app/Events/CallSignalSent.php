<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSignalSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversationId;
    public int $senderId;
    public string $action; // 'initiate', 'accept', 'decline', 'end', 'offer', 'answer', 'ice'
    public mixed $data;

    public function __construct(int $conversationId, int $senderId, string $action, mixed $data = null)
    {
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->action = $action;
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CallSignalSent';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'action' => $this->action,
            'data' => $this->data,
        ];
    }
}
