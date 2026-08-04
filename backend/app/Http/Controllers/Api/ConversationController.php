<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = Conversation::forUser($request->user()->id)
            ->with(['participants.user', 'latestMessage'])
            ->latest('updated_at')
            ->get();

        return response()->json(['data' => $conversations]);
    }

    public function store(CreateConversationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($request->type === 'direct') {
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->whereHas('participants', function ($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                })->first();

            if ($existing) {
                return response()->json(['data' => $existing->load('participants.user')]);
            }
        }

        $conversation = DB::transaction(function () use ($request, $user) {
            $conv = Conversation::create([
                'type' => $request->type,
                'name' => $request->name,
                'created_by' => $user->id,
            ]);

            // Add creator as admin
            $conv->participants()->create([
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            // Add others
            if ($request->type === 'direct') {
                $conv->participants()->create([
                    'user_id' => $request->user_id,
                    'role' => 'member',
                ]);
            } else {
                foreach ($request->participants as $participantId) {
                    if ($participantId !== $user->id) {
                        $conv->participants()->create([
                            'user_id' => $participantId,
                            'role' => 'member',
                        ]);
                    }
                }
            }

            return $conv;
        });

        return response()->json(['data' => $conversation->load('participants.user')], 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        return response()->json(['data' => $conversation->load('participants.user')]);
    }

    public function addParticipant(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        
        $admin = $conversation->participants()->where('user_id', $request->user()->id)->first();
        if (!$admin || $admin->role !== 'admin' || !$conversation->isGroup()) {
            return response()->json(['message' => 'Unauthorized to add members.'], 403);
        }

        $newUser = User::findOrFail($request->user_id);

        $conversation->participants()->firstOrCreate([
            'user_id' => $newUser->id,
        ], ['role' => 'member']);

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => "{$newUser->name} (@{$newUser->username}) was added to the group by " . $request->user()->name,
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Add Participant MessageSent failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Participant added',
            'conversation' => $conversation->fresh(['participants.user']),
            'system_message' => $loadedMessage
        ]);
    }

    public function removeParticipant(Request $request, Conversation $conversation, User $user): JsonResponse
    {
        $admin = $conversation->participants()->where('user_id', $request->user()->id)->first();
        if (!$admin || $admin->role !== 'admin' || !$conversation->isGroup()) {
            return response()->json(['message' => 'Unauthorized to remove members.'], 403);
        }

        $conversation->participants()->where('user_id', $user->id)->delete();

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => "{$user->name} (@{$user->username}) was removed from the group by " . $request->user()->name,
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Remove Participant MessageSent failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Participant removed',
            'conversation' => $conversation->fresh(['participants.user']),
            'system_message' => $loadedMessage
        ]);
    }

    public function updateParticipantRole(Request $request, Conversation $conversation, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        $admin = $conversation->participants()->where('user_id', $request->user()->id)->first();
        if (!$admin || $admin->role !== 'admin' || !$conversation->isGroup()) {
            return response()->json(['message' => 'Unauthorized to update roles.'], 403);
        }

        $participant = $conversation->participants()->where('user_id', $user->id)->firstOrFail();
        $participant->update(['role' => $request->role]);

        $actionText = $request->role === 'admin' ? 'promoted to Admin' : 'demoted to member';

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => "{$user->name} (@{$user->username}) was {$actionText} by " . $request->user()->name,
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Role Update MessageSent failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Role updated',
            'conversation' => $conversation->fresh(['participants.user']),
            'system_message' => $loadedMessage
        ]);
    }

    public function leave(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $conversation->participants()->where('user_id', $user->id)->delete();

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => "{$user->name} (@{$user->username}) left the group",
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Leave Group MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Left conversation']);
    }

    public function updateGroupInfo(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists() || !$conversation->isGroup()) {
            return response()->json(['message' => 'Unauthorized to update group info.'], 403);
        }

        $newName = $request->name;
        $conversation->update(['name' => $newName]);

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->user()->name . " changed the group name to \"{$newName}\"",
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Group Rename MessageSent failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Group name updated',
            'conversation' => $conversation->fresh(['participants.user']),
            'system_message' => $loadedMessage
        ]);
    }

    public function updateParticipantNickname(Request $request, Conversation $conversation, User $user): JsonResponse
    {
        $request->validate([
            'nickname' => 'nullable|string|max:100',
        ]);

        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Unauthorized to set nicknames.'], 403);
        }

        $participant = $conversation->participants()->where('user_id', $user->id)->firstOrFail();
        $newNickname = trim($request->nickname ?? '');

        $participant->update(['nickname' => $newNickname ?: null]);

        $updaterName = $request->user()->name;
        $targetName = $user->name;

        if ($newNickname) {
            $msgText = "{$updaterName} set the nickname for {$targetName} to \"{$newNickname}\"";
        } else {
            $msgText = "{$updaterName} cleared {$targetName}'s nickname";
        }

        // Insert System Message
        $systemMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $msgText,
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $systemMessage->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Nickname Update MessageSent failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Nickname updated',
            'conversation' => $conversation->fresh(['participants.user']),
            'system_message' => $loadedMessage
        ]);
    }
}
