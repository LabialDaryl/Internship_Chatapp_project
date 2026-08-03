<?php

use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{id}', function (User $user, int $id) {
    return ConversationParticipant::where('conversation_id', $id)
        ->where('user_id', $user->id)
        ->exists();
});
