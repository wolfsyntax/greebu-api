<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'language'              => $this->language->name ?? '',
            'language_id'           => $this->language->id ?? '',
            'mood'                  => $this->mood->name ?? '',
            'mood_id'               => $this->mood->id ?? '',
            'duration'              => $this->duration->title ?? '',
            'duration_id'           => $this->duration->id ?? '',
            'purpose'               => $this->purpose->name ?? '',
            'purpose_id'            => $this->purpose->id ?? '',
            'first_name'            => $this->first_name,
            'last_name'             => $this->last_name,
            'email'                 => $this->email,
            'sender'                => $this->sender,
            'receiver'              => $this->receiver,
            'user_story'            => $this->user_story,
            'page_status'           => $this->page_status,
            'verification_status'   => $this->verification_status,
            'delivery_date'         => $this->delivery_date,
            'estimate_date'         => $this->estimate_date,
            'approved_at'           => $this->approved_at,
            'approval_status'       => $this->approval_status,
            'artists'               => $this->artists,
        ];
    }
}
