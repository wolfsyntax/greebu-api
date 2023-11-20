<?php

namespace App\Http\Resources;

use App\Models\ArtistType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Libraries\AwsService;
use App\Models\ArtistGenres;
use DB;

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

        $avatar = $this->profile->avatar ?? '';
        $cover = $this->profile->cover_photo ?? '';

        // if ($this->profile->bucket && $avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
        //     if ($this->profile->bucket === 's3') {
        //         $avatar = Storage::disk('s3')->url($avatar);
        //     } else if ($this->profile->bucket === 's3priv') {
        //         $avatar = Storage::disk('s3priv')->temporaryUrl($avatar, now()->addMinutes(60));
        //     }
        // }

        // if ($this->profile->bucket && $cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
        //     if ($this->profile->bucket === 's3') {
        //         $cover = Storage::disk('s3')->url($cover);
        //     } else if ($this->profile->bucket === 's3priv') {
        //         $cover = Storage::disk('s3priv')->temporaryUrl($cover, now()->addMinutes(60));
        //     }
        // }

        $service = new AwsService();
        // $avatar = filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : ($this->bucket === 's3' ? Storage::disk($this->bucket)->url($this->avatar) : ($this->avatar ? Storage::disk($this->bucket)->temporaryUrl($this->avatar, now()->addMinutes(60)) : ''));

        $avatar = $this->profile->avatarUrl;
        $cover = $this->profile->bannerUrl;
        $audio = $this->song;

        if ($this->profile->bucket && in_array($this->profile->bucket, ['s3', 's3priv',])) {
            // if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
            //     $avatar = $service->get_aws_object($avatar, $this->profile->bucket === 's3priv');
            // }

            // if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
            //     $cover = $service->get_aws_object($cover, $this->profile->bucket === 's3priv');
            // }
        }

        if ($audio && !filter_var($audio, FILTER_VALIDATE_URL)) {
            $audio = $service->get_aws_object($audio);
        }

        $artist_type = '';

        if ($this->artist_type_id) {
            $type = ArtistType::find($this->artist_type_id);
            if ($type) {
                $artist_type = $type->title ?? '';
            }
            // $artist_type = (new ArtistTypeResource($this->artist_type_id))->title ?? '';
        }



        $genres = \App\Models\ArtistGenres::where('artist_id', $this->id)->get()->pluck('genre_title');

        return [
            'id'                    => $this->id,
            'profile_id'            => $this->profile->id,
            'artist_name'           => $this->profile->business_name ?? '',
            'artist_type_id'        => $this->artist_type_id ?? '',
            'artist_type'           => $artist_type,
            'avatar'                => $avatar,
            'cover_photo'           => $cover,
            'ratings'               => $this->avgRating,
            'reviews'               => count($this->reviews),
            'bio'                   => $this->profile->bio ?? '',
            'song_requests'         => $this->song_requests_count ?? 0,
            // 'genres'                 => $this->genres->pluck('genre_title'),
            'genres'                 => $genres,
            // 'genres2'                 => $this->genres,
            'song'                  => $audio, // ?? 'https://res.cloudinary.com/daorvtlls/video/upload/v1687411869/merrow-rock-skyline-pigeon-elton-john_h0chm4.mp3',
            'song_title'            => $this->song_title ?? '',
            'follower'              => $this->profile->followers_count ?? 0,
            'following'             => $this->profile->following_count ?? 0,
            'spotify'               => $this->profile->spotify,
            'youtube'               => $this->profile->youtube,
            'twitter'               => $this->profile->twitter,
            // 'threads'               => $this->profile->threads,
            'instagram'             => $this->profile->instagram,
            'street_address'        => $this->profile->street_address ?? '',
            'city'                  => $this->profile->city ?? '',
            'province'              => $this->profile->province ?? '',
            'accept_request'        => $this->accept_request,
            'accept_booking'        => $this->accept_booking,
            'accept_proposal'       => $this->accept_proposal,
        ];
        return parent::toArray($request);
    }
}
