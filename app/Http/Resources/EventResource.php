<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Organizer;
use App\Models\EventType;
use App\Models\Profile;
use App\Models\ArtistProposal;
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
        $this->load('profile');
        $avatar = $this->profile->avatarUrl;
        $cover =   $this->cover_photo ?? '';

        // if (!$avatar) {
        //     $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        // } else {
        //     $avatar_host = parse_url($avatar);
        //     if (!array_key_exists('host', $avatar_host)) {
        //         $avatar = $service->get_aws_object($avatar);
        //     }
        // }

        if ($cover) {
            $cover_host = parse_url($cover);
            if (!array_key_exists('host', $cover_host)) {
                $cover = $service->get_aws_object($cover);
            }
        }

        $seeking = \App\Models\LookType::select('look_type')->where('event_id', $this->id)->get()->map->look_type;

        // $accept_proposal = false;
        $artistId = '';

        $profile = null;
        $canSend = false;

        if (auth()->check()) {

            $profile = Profile::myAccount('artists')->first();

            if ($profile) {
                $artistId = $profile->artist->id;
                $canSend = !ArtistProposal::where('event_id', $this->id)->where('artist_id', $artistId)->whereNot('status', 'declined')->exists();
            }
            // if ($profile) $accept_proposal = true;
        }

        $proposals = ArtistProposal::with('artist.profile')->where('event_id', $this->id)->where('status', 'accepted')->whereNot('accepted_at', null)->get()->unique(['artist_id', ]);

        $data = [];

        foreach ($proposals as $proposal) {
            $artist = $proposal->artist;
            $data[] = [
                'artist_id'     => $artist->id ?? '',
                'profile_id'    => $artist->profile->id ?? '',
                'name'          => $artist->profile->business_name ?? '',
                'avatar'        => $artist->profile->avatar_url ?? '',
                'artist_type'   => $artist->artistType->title ?? '',
            ];

            // if ($canSend && $artist->id === $artistId) $canSend = false;
        }

        return [
            'id'                    => $this->id,
            'organizer_avatar'      => $avatar,
            'organizer_name'        => $this->profile->business_name ?? '',
            'organizer_company'     => $this->company_name ?? '',
            'organizer_id'          => $this->profile->organizer->id,
            'event_type'            => $this->event_type,
            'cover_photo'           => $cover,
            'event_name'            => $this->event_name,
            'venue_name'            => $this->venue_name,
            'location'              => $this->location,
            'street_address'        => $this->street_address,
            'barangay'              => $this->barangay,
            'city'                  => $this->city,
            'province'              => $this->province,
            'audience'              => $this->audience,
            'start_date'            => $this->start_date->format('Y-m-d'),
            'end_date'              => $this->end_date->format('Y-m-d'),
            'start_time'            => date_format($this->start_time, 'H:i'),
            'end_time'              => date_format($this->end_time, 'H:i'),
            'description'           => $this->description,
            'lat'                   => $this->lat,
            'long'                  => $this->long,
            'capacity'              => $this->capacity ?? 1,
            'is_featured'           => $this->is_featured,
            'is_free'               => $this->is_free,
            'status'                => $this->status,
            'review_status'         => $this->review_status,
            // What are you looking for?
            'look_for'              => $this->look_for,
            'look_types'            => $seeking,
            'requirement'           => $this->requirement,
            'created_at'            => $this->created_at,
            'accept_proposal'       => $this->profile->organizer->accept_proposal ?? false,
            'artist'                => $this->when($data, $data),
            'is_cancelled'          => $this->deleted_at ? true : false,
            'reason'                => $this->reason,
            // 'cs'                    => ArtistProposal::where('event_id', $this->id)->where('artist_id', $artistId)->whereNot('status', 'declined')->get(),
            'can_send'              => $this->when($artistId, $canSend),
            'total_participants'    => $this->total_participants ?? 0,
            'is_visible'            => (count($data) >= $this->total_participants),
        ];
        return parent::toArray($request);
    }
}
