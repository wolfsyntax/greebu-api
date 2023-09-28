<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Organizer;
use App\Models\EventType;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'                => $this->id,
            'organizer_id'      => $this->organizer_id,
            'event_types_id'    => $this->event_types_id,
            'cover_photo'       => $this->cover_photo,
            'event_name'        => $this->event_name,
            'location'          => $this->location,
            'audience'          => $this->audience,
            'start_date'        => $this->start_date,
            'end_date'          => $this->end_date,
            'start_time'        => date_format($this->start_time, 'H:i'),
            'end_time'          => date_format($this->end_time, 'H:i'),
            'description'       => $this->description,
            'lat'               => $this->lat,
            'long'              => $this->long,
            'capacity'          => $this->capacity ?? 1,
            'is_featured'       => $this->is_featured,
            'is_free'           => $this->is_free,
            'status'            => $this->status,
            'review_status'     => $this->review_status,
            // What are you looking for?
            'look_for'          => $this->look_for,
            'look_type'         => $this->look_type,
            'requirement'       => $this->requirement,
            'organizer'         => Organizer::find($this->organizer_id),
            'event_type'        => EventType::find($this->event_types_id),
        ];
        return parent::toArray($request);
    }
}
