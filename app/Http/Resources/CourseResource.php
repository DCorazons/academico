<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'volume' => $this->volume,
            'spots' => $this->spots,
            // Eloquent casts these columns to dates, the query builder returns strings
            'start_date' => Carbon::parse($this->start_date)->toDateString(),
            'end_date' => Carbon::parse($this->end_date)->toDateString(),
            'period' => [
                'id' => $this->period->id,
                'name' => $this->period->name,
            ],
            'teacher' => [
                'id' => $this->teacher->id,
                'firstname' => $this->teacher->user->firstname,
                'lastname' => $this->teacher->user->lastname,
            ],
        ];
    }
}
