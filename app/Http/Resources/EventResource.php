<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Organizer;
use App\Models\EventType;
use App\Libraries\AwsService;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $service = new AwsService();
        $this->load('organizer.profile');
        $avatar = $this->organizer->profile->avatar;
        $cover =   $this->cover_photo ?? '';

        if (!$avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->organizer->profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($this->avatar);
            }
        }

        if ($cover) {
            $cover_host = parse_url($cover);
            if (!array_key_exists('host', $cover_host)) {
                $cover = $service->get_aws_object($this->cover);
            }
        }

        return [
            'id'                => $this->id,
            'organizer_avatar'  => $avatar,
            'organizer_name'    => $this->organizer->profile->business_name ?? '',
            'organizer_id'      => $this->organizer_id,
            'event_types_id'    => $this->event_types_id,
            'cover_photo'       => $cover,
            'event_name'        => $this->event_name,
            'location'          => $this->location,
            'street_address'    => $this->street_address,
            'barangay'          => $this->barangay,
            'city'              => $this->city,
            'province'          => $this->province,
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
