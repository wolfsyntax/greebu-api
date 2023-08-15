<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistFullResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $this->profile->loadCount('followers', 'following');
        $avatar = filter_var($this->profile->avatar, FILTER_VALIDATE_URL) ? $this->profile->avatar : ($this->profile->bucket === 's3' ? Storage::disk($this->profile->bucket)->url($this->profile->avatar) : ($this->profile->avatar ? Storage::disk($this->profile->bucket)->temporaryUrl($this->profile->avatar, now()->addMinutes(60)) : ''));

        return [
            'id'                    => $this->id,
            'artist_name'           => $this->profile->business_name,
            'artist_type'           => (new ArtistTypeResource($this->artistType))->title,
            'avatar'                => $avatar,
            'ratings'               => $this->avgRating,
            'reviews'               => count($this->reviews),
            'bio'                   => $this->profile->bio,
            'song_requests'         => $this->song_requests_count ?? 0,
            'genres'                 => collect($this->genres)->pluck('title'),
            'song'                  => 'https://res.cloudinary.com/daorvtlls/video/upload/v1687411869/merrow-rock-skyline-pigeon-elton-john_h0chm4.mp3',
            'follower'              => $this->profile->followers_count,
            'following'             => $this->profile->following_count,
            'spotify_profile'       => $this->spotify_profile,
            'youtube_channel'       => $this->youtube_channel,
            'twitter_username'      => $this->twitter_username,
            'instagram_username'    => $this->instagram_username,
            'street_address'        => $this->profile->street_address,
            'city'                  => $this->profile->city,
            'province'              => $this->profile->province,
            'accept_request'        => $this->accept_request,
            'accept_booking'        => $this->accept_booking,
            'accept_proposal'       => $this->accept_proposal,
        ];
        return parent::toArray($request);
    }
}
