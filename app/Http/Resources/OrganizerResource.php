<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Libraries\AwsService;

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

        $avatar = $this->profile->avatar ?? '';
        $cover =  $this->profile->cover_photo ?? '';

        $service = new AwsService();

        if ($this->profile->bucket && in_array($this->profile->bucket, ['s3', 's3priv',])) {
            if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
                $avatar = $service->get_aws_object($avatar, false);
            }

            if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
                $cover = $service->get_aws_object($cover, false);
            }
        }

        return [
            'id'                => $this->id,
            'organizer_name'    => $this->profile->business_name,
            'company_name'      => $this->company_name,
            'avatar'            => $avatar,
            'cover_photo'       => $cover,
            'event_types'       => $eventTypes,
            'street_address'    => $this->profile->street_address ?? '',
            'city'              => $this->profile->city ?? '',
            'province'          => $this->profile->province ?? '',
            'accept_proposal'   => $this->accept_proposal,
            'send_proposal'     => $this->send_proposal,
            'facebook'          => $this->profile->facebook,
            'twitter'           => $this->profile->twitter,
            'instagram'         => $this->profile->instagram,
            'threads'           => $this->profile->threads,
            'bio'               => $this->profile->bio,
        ];

        return parent::toArray($request);
    }
}
