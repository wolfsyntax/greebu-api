<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Libraries\AwsService;

/**
 * @property \App\Models\Profile $profile
 * @property string $song
 * @property \App\Models\ArtistType $artistType
 * @property \App\Models\ArtistReview $reviews
 * @property \App\Models\Genre $genres
 * @property string $id
 *
 */
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

        $this->load('genres');

        $service = new AwsService();

        $avatar = $this->profile->avatarUrl;
        $cover = $this->profile->bannerUrl;
        $audio = $this->song;

        if ($audio && !filter_var($audio, FILTER_VALIDATE_URL)) {
            $audio = $service->get_aws_object($audio);
        }

        return [
            'id'                    => $this->id,
            'artist_name'           => $this->profile->business_name,
            'artist_type'           => (new ArtistTypeResource($this->artistType))->title ?? '',
            'avatar'                => $avatar ?? '',
            'ratings'               => $this->avgRating,
            'reviews'               => count($this->reviews),
            'bio'                   => $this->profile->bio,
            'song_requests'         => $this->song_requests_count ?? 0,
            'genres'                => $this->genres->pluck('genre_title'), //collect($this->genres)->pluck('genre_title'),
            'song'                  => $audio ?? 'https://res.cloudinary.com/daorvtlls/video/upload/v1687411869/merrow-rock-skyline-pigeon-elton-john_h0chm4.mp3',
            'song_title'            => $this->song_title ?? '',
            'cover'                 => $cover ?? '',
            'follower'              => $this->profile->followers_count,
            'following'             => $this->profile->following_count,
            // Social Media Links
            'spotify'               => $this->profile->spotify,
            'youtube'               => $this->profile->youtube,
            'twitter'               => $this->profile->twitter,
            'instagram'             => $this->profile->instagram,
        ];
    }
}
