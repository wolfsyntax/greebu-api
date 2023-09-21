<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Libraries\AwsService;

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


        // $avatar = $this->profile->avatar;

        // if ($this->profile->bucket && !filter_var($avatar, FILTER_VALIDATE_URL)) {
        //     if ($this->profile->bucket === 's3') {
        //         $avatar = Storage::disk($this->profile->bucket)->url($avatar);
        //     } else if ($this->profile->bucket === 's3priv') {
        //         $avatar = Storage::disk($this->profile->bucket)->temporaryUrl($this->profile->avatar, now()->addMinutes(60));
        //     }
        // }


        $this->load('genres');

        $service = new AwsService();

        $avatar = $this->profile->avatar;
        $cover = $this->profile->cover_photo;
        $audio = $this->song;

        // if ($this->profile->bucket && in_array($this->profile->bucket, ['s3', 's3priv',])) {
        //     if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
        //         $avatar = $service->get_aws_object($avatar, $this->profile->bucket === 's3priv');
        //     }

        //     if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
        //         $cover = $service->get_aws_object($cover, $this->profile->bucket === 's3priv');
        //     }
        // }

        // if ($audio && !filter_var($audio, FILTER_VALIDATE_URL)) {
        //     $audio = $service->get_aws_object($audio);
        // }

        if (!$this->profile->avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->profile->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($this->profile->avatar);
            }
        }

        if (!$this->profile->cover_photo) {
            // $cover = 'https://ui-avatars.com/api/?name=' . substr($this->business_name,  0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $cover_host = parse_url($cover);
            if (!array_key_exists('host', $cover_host)) {
                $cover = $service->get_aws_object($this->profile->cover_photo);
            }
        }

        if ($audio && !filter_var($audio, FILTER_VALIDATE_URL)) {
            $audio = $service->get_aws_object($audio);
        }

        return [
            'id'                    => $this->id,
            'artist_name'           => $this->profile->business_name,
            'artist_type'           => (new ArtistTypeResource($this->artistType))->title,
            'avatar'                => $avatar ?? '',
            'ratings'               => $this->avgRating,
            'reviews'               => count($this->reviews),
            'bio'                   => $this->profile->bio,
            'song_requests'         => $this->song_requests_count ?? 0,
            'genres'                => $this->genres->pluck('genre_title'), //collect($this->genres)->pluck('genre_title'),
            'song'                  => $audio ?? 'https://res.cloudinary.com/daorvtlls/video/upload/v1687411869/merrow-rock-skyline-pigeon-elton-john_h0chm4.mp3',
            'cover'                 => $cover ?? '',
            'follower'              => $this->profile->followers_count,
            'following'             => $this->profile->following_count,
            // Social Media Links
            'spotify'               => $this->profile->spotify,
            'youtube'               => $this->profile->youtube,
            'twitter'               => $this->profile->twitter,
            'instagram'             => $this->profile->instagram,
        ];
        return parent::toArray($request);
    }
}
