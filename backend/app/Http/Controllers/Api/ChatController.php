<?php

namespace App\Http\Controllers\Api;

use App\Events\CallSignalSent;
use App\Events\MessageDeleted;
use App\Events\MessagePinned;
use App\Events\MessageRead;
use App\Events\MessageReactionUpdated;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\CallLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\ReadReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'readReceipts', 'parent', 'reactions'])
            ->latest()
            ->cursorPaginate(50);

        return response()->json($messages);
    }

    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'parent_id' => $request->parent_id ?? null,
            'body' => $request->body,
            'type' => $request->type ?? 'text',
        ]);

        $conversation->touch();

        $loadedMessage = $message->load(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }

    public function uploadAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        
        $path = $file->store('attachments', 'public');
        $url = asset('storage/' . $path);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'parent_id' => $request->parent_id ?? null,
            'body' => $url,
            'type' => $isImage ? 'image' : 'file',
        ]);

        $conversation->touch();

        $loadedMessage = $message->load(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageSent attachment failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }

    public function uploadVoiceNote(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'audio' => 'required|file|max:10240',
        ]);

        $file = $request->file('audio');
        $path = $file->store('voice_notes', 'public');
        $url = asset('storage/' . $path);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'parent_id' => $request->parent_id ?? null,
            'body' => $url,
            'type' => 'audio',
        ]);

        $conversation->touch();

        $loadedMessage = $message->load(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageSent voice note failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }

    public function togglePinMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $message->update([
            'is_pinned' => !$message->is_pinned,
        ]);

        $loadedMessage = $message->fresh(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessagePinned($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessagePinned failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage]);
    }

    public function toggleReaction(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'emoji' => 'required|string|max:16',
        ]);

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $request->user()->id)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $request->user()->id,
                'emoji' => $request->emoji,
            ]);
        }

        try {
            broadcast(new MessageReactionUpdated($message))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageReactionUpdated failed: ' . $e->getMessage());
        }

        $updatedReactions = $message->reactions()->with('user:id,name,username')->get();

        return response()->json(['data' => $updatedReactions]);
    }

    public function updateMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        if ($message->sender_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized to edit this message.'], 403);
        }

        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message->update([
            'body' => $request->body,
            'is_edited' => true,
        ]);

        $loadedMessage = $message->fresh(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessageUpdated($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageUpdated failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage]);
    }

    public function destroyMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $isSender = $message->sender_id === $request->user()->id;
        $isAdmin = $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->where('role', 'admin')
            ->exists();

        if (!$isSender && !$isAdmin) {
            return response()->json(['message' => 'Unauthorized to delete this message.'], 403);
        }

        $message->update([
            'body' => 'This message was deleted',
            'is_deleted' => true,
        ]);

        try {
            broadcast(new MessageDeleted($message->id, $conversation->id))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageDeleted failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Message deleted successfully', 'id' => $message->id]);
    }

    public function forwardMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $request->validate([
            'target_conversation_id' => 'required|exists:conversations,id',
        ]);

        $targetConversation = Conversation::findOrFail($request->target_conversation_id);

        if (!$targetConversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $forwardedMessage = $targetConversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $message->body,
            'type' => $message->type,
        ]);

        $targetConversation->touch();

        $loadedMessage = $forwardedMessage->load(['sender', 'parent', 'reactions']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Forwarded MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }

    public function sendCallSignal(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|string',
            'data' => 'nullable',
        ]);

        try {
            broadcast(new CallSignalSent(
                $conversation->id,
                $request->user()->id,
                $request->action,
                $request->data
            ))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting CallSignalSent failed: ' . $e->getMessage());
        }

        return response()->json(['status' => 'signal_sent']);
    }

    public function logCall(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'type' => 'required|string|in:audio,video',
            'status' => 'required|string|in:completed,missed,declined',
            'duration_seconds' => 'nullable|integer',
        ]);

        $callLog = CallLog::create([
            'conversation_id' => $conversation->id,
            'caller_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id ?? null,
            'type' => $request->type,
            'status' => $request->status,
            'duration_seconds' => $request->duration_seconds ?? 0,
        ]);

        $body = match ($request->status) {
            'completed' => "Call ended (" . floor($callLog->duration_seconds / 60) . "m " . ($callLog->duration_seconds % 60) . "s)",
            'declined' => "Call declined",
            default => "Missed " . ucfirst($request->type) . " Call",
        };

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $body,
            'type' => 'system',
        ]);

        $conversation->touch();
        $loadedMessage = $message->load(['sender']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Call Log MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $callLog], 201);
    }

    public function getMessageReadReceipts(Request $request, Message $message): JsonResponse
    {
        $receipts = ReadReceipt::where('message_id', $message->id)
            ->with('user:id,name,username,avatar_url')
            ->get();

        return response()->json(['data' => $receipts]);
    }

    public function getConversationMedia(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $media = $conversation->messages()
            ->whereIn('type', ['image', 'file', 'audio'])
            ->where('is_deleted', false)
            ->with('sender:id,name,username')
            ->latest()
            ->get();

        return response()->json(['data' => $media]);
    }

    public function searchMessages(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $q = $request->query('query');
        if (!$q) {
            return response()->json([]);
        }

        $results = $conversation->messages()
            ->where('is_deleted', false)
            ->where('body', 'LIKE', '%' . $q . '%')
            ->with(['sender'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json($results);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereDoesntHave('readReceipts', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->pluck('id');

        $receipts = [];
        foreach ($unreadMessages as $messageId) {
            $receipts[] = [
                'message_id' => $messageId,
                'user_id' => $request->user()->id,
                'read_at' => now(),
            ];
        }

        if (count($receipts) > 0) {
            ReadReceipt::insert($receipts);
            try {
                broadcast(new MessageRead($conversation->id, $request->user()->id))->toOthers();
            } catch (\Throwable $e) {
                logger()->warning('Broadcasting MessageRead failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Messages marked as read']);
    }
}
