<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Libraries\AwsService;

class ArtistProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new AwsService();

        $event = $this->event;
        $artist = $this->artist;
        $organizer = $this->event->organizer;

        $artist_profile = $this->artist->profile;
        $organizer_profile = $this->event->organizer->profile;

        $organizer_avatar = $organizer_profile->avatar;
        $artist_avatar = $artist_profile->avatar;

        $cover_photo = $event->cover_photo;
        $song = $this->sample_song;

        if (!$organizer_avatar) {
            $organizer_avatar = 'https://ui-avatars.com/api/?name=' . substr($organizer_profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($organizer_avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $organizer_avatar = $service->get_aws_object($organizer_avatar);
            }
        }

        if (!$artist_avatar) {
            $artist_avatar = 'https://ui-avatars.com/api/?name=' . substr($artist_profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($artist_avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $artist_avatar = $service->get_aws_object($artist_avatar);
            }
        }

        if (!$cover_photo) {
            $cover_photo = 'https://ui-avatars.com/api/?name=' . substr($event->event_name, '', 0, 1) . '&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $cover_host = parse_url($cover_photo);
            if (!array_key_exists('host', $cover_host)) {
                $cover_photo = $service->get_aws_object($cover_photo);
            }
        }

        if ($song) {

            $song_host = parse_url($song);
            if (!array_key_exists('host', $song_host)) {
                $song = $service->get_aws_object($song);
            }
        }

        return [
            'id'       => $this->id,
            'organizer_name'    => $organizer_profile->business_name,
            'cover_photo'       => $cover_photo,
            'organizer_avatar'  => $organizer_avatar,
            'event_name'        => $event->event_name,
            'is_public'         => $event->is_public ? 'Public Event' : 'Private Event',
            'created_at'        => $event->created_at,
            'start_date'        => $event->start_date,
            'end_date'          => $event->end_date,
            'start_time'        => $event->start_time,
            'end_time'          => $event->end_time,
            'venue_name'        => $event->venue_name,
            'location'          => $event->location,
            'street_address'    => $event->street_address,
            'barangay'          => $event->barangay,
            'city'              => $event->city,
            'province'          => $event->province,
            'artist_name'       => $artist->profile->business_name,
            'artist_avatar'     => $artist_avatar ?? '',
            'total_member'      => $this->total_member,
            'genres'            => $artist->genres->pluck('genre_title'),
            'cover_letter'      => $this->cover_letter,
            'status'            => $this->status,
            'song'              => $song,
            'accepted_at'       => $this->accepted_at,
            'declined_at'       => $this->declined_at,
        ];

        return parent::toArray($request);
    }
}
