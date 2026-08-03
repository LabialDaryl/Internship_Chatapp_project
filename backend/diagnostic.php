<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::beginTransaction();

    echo "1. Creating Users...\n";
    $user1 = User::create([
        'name' => 'Test User A',
        'email' => 'a@example.com',
        'password' => Hash::make('password123')
    ]);

    $user2 = User::create([
        'name' => 'Test User B',
        'email' => 'b@example.com',
        'password' => Hash::make('password123')
    ]);

    echo "2. Creating Conversation...\n";
    $conv = Conversation::create([
        'type' => 'direct',
        'created_by' => $user1->id
    ]);

    echo "3. Attaching Participants...\n";
    $conv->participants()->create([
        'user_id' => $user1->id,
        'role' => 'admin'
    ]);
    $conv->participants()->create([
        'user_id' => $user2->id,
        'role' => 'member'
    ]);

    echo "4. Sending Message...\n";
    $message = $conv->messages()->create([
        'sender_id' => $user1->id,
        'body' => 'Hello from User A',
        'type' => 'text'
    ]);

    echo "5. Testing Read Receipts...\n";
    $message->readReceipts()->create([
        'user_id' => $user2->id,
        'read_at' => now()
    ]);

    echo "6. Testing Relationships...\n";
    $fetchedConv = Conversation::with(['participants.user', 'messages.readReceipts'])->find($conv->id);
    
    if ($fetchedConv->participants->count() !== 2) throw new Exception("Participants count mismatch");
    if ($fetchedConv->messages->count() !== 1) throw new Exception("Messages count mismatch");
    if (!$fetchedConv->messages->first()->isReadBy($user2->id)) throw new Exception("isReadBy method failed");

    echo "SUCCESS: All models and relationships are working perfectly!\n";

    DB::rollBack();
    echo "Rollback successful. Database remains clean.\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "FAILED: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
