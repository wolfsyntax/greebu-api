<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->profile->loadCount('followers', 'following');

        return [
            'id'            => $this->id,
            'profile_id'    => $this->profile_id,
            'follower'      => $this->profile->followers_count,
            'following'     => $this->profile->following_count,
            'artist_name'   => $this->profile->business_name,
            'artist_type'   => (new ArtistTypeResource($this->artistType))->title,
            'avatar'        => $this->profile->avatar,
            'ratings'       => $this->avg_rating,
            'reviews'       => count($this->reviews),
            'bio'           => $this->profile->bio,
            'genres'        => new GenreCollection($this->genres),
        ];
    }
}
