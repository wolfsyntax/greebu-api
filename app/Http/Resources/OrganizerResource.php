<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $eventTypes = \App\Models\OrganizerEventTypes::where('organizer_id', $this->id)->get()->pluck('event_type');

        return [
            'organizer_name'    => $this->profile->business_name,
            'event_types'       => $eventTypes,
            'street_address'    => $this->profile->street_address ?? '',
            'city'              => $this->profile->city ?? '',
            'province'          => $this->profile->province ?? '',
            'accept_proposal'   => $this->accept_proposal,
            'send_proposal'     => $this->send_proposal,
            'facebook'          => $this->profile->facebook,
            'twitter'           => $this->profile->twitter,
            'instagram'         => $this->profile->instagram,
            'bio'               => $this->profile->bio,
        ];

        return parent::toArray($request);
    }
}
