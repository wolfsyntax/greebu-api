<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $this->profile->loadCount('followers', 'following');
        // $avatar = filter_var($this->profile->avatar, FILTER_VALIDATE_URL) ? $this->profile->avatar : ($this->profile->bucket === 's3' ? Storage::disk($this->profile->bucket)->url($this->profile->avatar) : ($this->profile->avatar ? Storage::disk($this->profile->bucket)->temporaryUrl($this->profile->avatar, now()->addMinutes(60)) : ''));

        $avatar = $this->profile->avatar;

        if ($this->profile->bucket && !filter_var($avatar, FILTER_VALIDATE_URL)) {
            if ($this->profile->bucket === 's3') {
                $avatar = Storage::disk($this->profile->bucket)->url($avatar);
            } else {
                $avatar = Storage::disk($this->profile->bucket)->temporaryUrl($this->profile->avatar, now()->addMinutes(60));
            }
        }

        return [
            'id'                    => $this->id,
            'artist_name'           => $this->profile->business_name,
            'artist_type'           => (new ArtistTypeResource($this->artistType))->title,
            'avatar'                => $avatar,
            'ratings'               => $this->avgRating,
            'reviews'               => count($this->reviews),
            'bio'                   => $this->profile->bio,
            'song_requests'         => $this->song_requests_count ?? 0,
            'genres'                => new GenreCollection($this->genres),
            'song'                  => 'https://res.cloudinary.com/daorvtlls/video/upload/v1687411869/merrow-rock-skyline-pigeon-elton-john_h0chm4.mp3',
            'follower'              => $this->profile->followers_count,
            'following'             => $this->profile->following_count,
        ];
        return parent::toArray($request);
    }
}
