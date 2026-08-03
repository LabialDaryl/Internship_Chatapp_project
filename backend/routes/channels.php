<?php

use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{id}', function ($user, $id) {
    if (!$user) return false;
    return ConversationParticipant::where('conversation_id', (int) $id)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('presence-chat', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
        ];
    }
    return false;
});
