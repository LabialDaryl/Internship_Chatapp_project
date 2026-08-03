<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Sample Users
        $daryl = User::create([
            'name'          => 'Daryl',
            'username'      => 'daryl',
            'email'         => 'daryl@example.com',
            'date_of_birth' => '1995-05-15',
            'password'      => Hash::make('password'),
            'is_online'     => true,
            'last_seen_at'  => now(),
        ]);

        $alice = User::create([
            'name'          => 'Alice Vance',
            'username'      => 'alice',
            'email'         => 'alice@example.com',
            'date_of_birth' => '1998-08-20',
            'password'      => Hash::make('password'),
            'is_online'     => true,
            'last_seen_at'  => now(),
        ]);

        $bob = User::create([
            'name'          => 'Bob Miller',
            'username'      => 'bob',
            'email'         => 'bob@example.com',
            'date_of_birth' => '1996-03-10',
            'password'      => Hash::make('password'),
            'is_online'     => false,
            'last_seen_at'  => now()->subMinutes(15),
        ]);

        $charlie = User::create([
            'name'          => 'Charlie Davis',
            'username'      => 'charlie',
            'email'         => 'charlie@example.com',
            'date_of_birth' => '2000-11-25',
            'password'      => Hash::make('password'),
            'is_online'     => false,
            'last_seen_at'  => now()->subHours(2),
        ]);

        // 2. Direct Conversation 1: Daryl & Alice
        $conv1 = Conversation::create([
            'type' => 'direct',
            'created_by' => $daryl->id,
        ]);
        ConversationParticipant::create(['conversation_id' => $conv1->id, 'user_id' => $daryl->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv1->id, 'user_id' => $alice->id, 'role' => 'member']);

        Message::create(['conversation_id' => $conv1->id, 'sender_id' => $alice->id, 'body' => 'Hey Daryl! Welcome to the new real-time chat app. 👋', 'type' => 'text']);
        Message::create(['conversation_id' => $conv1->id, 'sender_id' => $daryl->id, 'body' => 'Hi Alice! The Vibrant Violet design and WebSockets feel super fast!', 'type' => 'text']);

        // 3. Direct Conversation 2: Daryl & Bob
        $conv2 = Conversation::create([
            'type' => 'direct',
            'created_by' => $daryl->id,
        ]);
        ConversationParticipant::create(['conversation_id' => $conv2->id, 'user_id' => $daryl->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $conv2->id, 'user_id' => $bob->id, 'role' => 'member']);

        Message::create(['conversation_id' => $conv2->id, 'sender_id' => $bob->id, 'body' => 'Did you check out the new attachment sharing feature?', 'type' => 'text']);

        // 4. Group Conversation: "Dev Team"
        $group = Conversation::create([
            'type' => 'group',
            'name' => 'Dev Team',
            'created_by' => $daryl->id,
        ]);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $daryl->id, 'role' => 'admin']);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $alice->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $bob->id, 'role' => 'member']);
        ConversationParticipant::create(['conversation_id' => $group->id, 'user_id' => $charlie->id, 'role' => 'member']);

        Message::create(['conversation_id' => $group->id, 'sender_id' => $charlie->id, 'body' => 'Good morning team! Phase 6 features are looking awesome.', 'type' => 'text']);
        Message::create(['conversation_id' => $group->id, 'sender_id' => $alice->id, 'body' => 'Agreed! Let us test group messages and image attachments.', 'type' => 'text']);
    }
}
