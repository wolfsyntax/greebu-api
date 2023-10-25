<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_story'                => $this->user_story ?? '',
            'song_request_id'                => $this->id ?? '',
            'purpose'                   => $this->purpose->name ?? '',
            'mood'                      => $this->mood->name ?? '',
            // 'genre'
            'language'                  => $this->language->name ?? '',
            'creator'                   => [
                'name'                  => $this->creator->business_name,
                'email'                 => $this->creator->business_email,
            ],
            'duration'                  => $this->duration->title ?? '',
            'sender'                    => $this->sender,
            'receiver'                  => $this->receiver,
            'purpose'                   => $this->purpose->name ?? '',
            'first_name'                => $this->first_name,
            'last_name'                 => $this->last_name,
            'email'                     => $this->email,
            'page_status'               => $this->page_status,
            'verification_status'       => $this->verification_status,
            'delivery_date'             => $this->delivery_date,
            'estimate_date'             => $this->estimate_date,
            'approved_at'               => $this->approved_at,
            'approval_status'           => $this->approval_status,
            'artists'                   => new ArtistCollection($this->artists),
        ];
        return parent::toArray($request);
    }
}
