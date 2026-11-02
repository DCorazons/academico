<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hired_at' => $this->hired_at,
            'max_week_hours' => $this->max_week_hours,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'firstname' => $this->user->firstname,
                'lastname' => $this->user->lastname,
                'email' => $this->user->email,
            ],
        ];
    }
}
