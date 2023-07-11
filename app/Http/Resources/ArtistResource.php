<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {

        return [
            'id'            => $this->id,
            'artist_name'   => $this->profile->business_name,
            'artist_type'   => (new ArtistTypeResource($this->artistType))->title,
            'avatar'        => $this->profile->avatar,
            'ratings'       => $this->avgRating,
            'reviews'       => count($this->reviews),
            'genres'        => new GenreCollection($this->genres),
        ];
        return parent::toArray($request);
    }
}
