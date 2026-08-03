<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageDeleted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
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
            ->with(['sender', 'readReceipts', 'parent'])
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

        $loadedMessage = $message->load(['sender', 'parent']);

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

        $loadedMessage = $message->load(['sender', 'parent']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageSent attachment failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
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

        $loadedMessage = $message->fresh(['sender', 'parent']);

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

        // Allow sender or conversation admin to delete
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

        $loadedMessage = $forwardedMessage->load(['sender', 'parent']);

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting Forwarded MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
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
            \App\Models\ReadReceipt::insert($receipts);
            try {
                broadcast(new MessageRead($conversation->id, $request->user()->id))->toOthers();
            } catch (\Throwable $e) {
                logger()->warning('Broadcasting MessageRead failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Messages marked as read']);
    }
}
