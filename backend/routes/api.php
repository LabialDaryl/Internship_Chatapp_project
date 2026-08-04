<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (no authentication required)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (require Sanctum token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Conversations
    Route::apiResource('conversations', ConversationController::class)->only(['index', 'store', 'show']);
    Route::post('/conversations/{conversation}/participants', [ConversationController::class, 'addParticipant']);
    Route::put('/conversations/{conversation}/participants/{user}/role', [ConversationController::class, 'updateParticipantRole']);
    Route::delete('/conversations/{conversation}/participants/{user}', [ConversationController::class, 'removeParticipant']);
    Route::post('/conversations/{conversation}/leave', [ConversationController::class, 'leave']);

    // Messages & Calling
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'send']);
    Route::post('/conversations/{conversation}/attachments', [ChatController::class, 'uploadAttachment']);
    Route::post('/conversations/{conversation}/voice-notes', [ChatController::class, 'uploadVoiceNote']);
    Route::put('/conversations/{conversation}/messages/{message}', [ChatController::class, 'updateMessage']);
    Route::delete('/conversations/{conversation}/messages/{message}', [ChatController::class, 'destroyMessage']);
    Route::post('/conversations/{conversation}/messages/{message}/forward', [ChatController::class, 'forwardMessage']);
    Route::post('/conversations/{conversation}/messages/{message}/reactions', [ChatController::class, 'toggleReaction']);
    Route::post('/conversations/{conversation}/messages/{message}/pin', [ChatController::class, 'togglePinMessage']);
    Route::get('/messages/{message}/read-receipts', [ChatController::class, 'getMessageReadReceipts']);
    Route::get('/conversations/{conversation}/media', [ChatController::class, 'getConversationMedia']);
    Route::post('/conversations/{conversation}/call-signal', [ChatController::class, 'sendCallSignal']);
    Route::post('/conversations/{conversation}/call-logs', [ChatController::class, 'logCall']);
    Route::get('/conversations/{conversation}/search-messages', [ChatController::class, 'searchMessages']);
    Route::post('/conversations/{conversation}/read', [ChatController::class, 'markRead']);

    // Contacts
    Route::get('/contacts/search', [ContactController::class, 'search']);

    // Real-Time Broadcasting Authentication
    \Illuminate\Support\Facades\Broadcast::routes();
});

