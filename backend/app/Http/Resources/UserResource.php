<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'username'      => $this->username,
            'email'         => $this->email,
            'bio'           => $this->bio,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'avatar_url'    => $this->avatar_url,
            'is_online'     => $this->is_online,
            'last_seen_at'  => $this->last_seen_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
