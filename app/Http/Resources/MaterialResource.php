<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /courses/{id}/materials */
class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meeting' => $this->meeting,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
}
