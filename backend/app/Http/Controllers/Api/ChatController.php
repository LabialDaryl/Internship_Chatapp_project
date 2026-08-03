<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
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
            ->with(['sender', 'readReceipts'])
            ->latest()
            ->cursorPaginate(30);

        return response()->json($messages);
    }

    public function send(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->body,
            'type' => $request->type ?? 'text',
        ]);

        $conversation->touch(); // Update updated_at

        $loadedMessage = $message->load('sender');

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            // Log or ignore if Reverb server is not running locally
            logger()->warning('Broadcasting MessageSent failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        // Simplistic mark all as read for conversation
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

    public function uploadAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        
        $path = $file->store('attachments', 'public');
        $url = asset('storage/' . $path);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $url,
            'type' => $isImage ? 'image' : 'file',
        ]);

        $conversation->touch();

        $loadedMessage = $message->load('sender');

        try {
            broadcast(new MessageSent($loadedMessage))->toOthers();
        } catch (\Throwable $e) {
            logger()->warning('Broadcasting MessageSent attachment failed: ' . $e->getMessage());
        }

        return response()->json(['data' => $loadedMessage], 201);
    }
}
