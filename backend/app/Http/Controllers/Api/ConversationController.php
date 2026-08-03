<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\User;

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
            // Check if direct conversation already exists
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
        
        $participant = $conversation->participants()->where('user_id', $request->user()->id)->first();
        if (!$participant || $participant->role !== 'admin' || !$conversation->isGroup()) {
            abort(403);
        }

        $conversation->participants()->firstOrCreate([
            'user_id' => $request->user_id,
        ], ['role' => 'member']);

        return response()->json(['message' => 'Participant added']);
    }

    public function removeParticipant(Request $request, Conversation $conversation, User $user): JsonResponse
    {
        $participant = $conversation->participants()->where('user_id', $request->user()->id)->first();
        if (!$participant || $participant->role !== 'admin' || !$conversation->isGroup()) {
            abort(403);
        }

        $conversation->participants()->where('user_id', $user->id)->delete();
        return response()->json(['message' => 'Participant removed']);
    }

    public function leave(Request $request, Conversation $conversation): JsonResponse
    {
        $conversation->participants()->where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'Left conversation']);
    }
}
