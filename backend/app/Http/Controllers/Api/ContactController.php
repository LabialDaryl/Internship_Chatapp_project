<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q');
        
        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        $users = User::where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('email', 'ilike', "%{$query}%");
            })
            ->select('id', 'name', 'email', 'avatar_url', 'is_online', 'last_seen_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $users]);
    }
}
