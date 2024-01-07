<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

use App\Libraries\AwsService;

/**
 * @property string $avatar
 * @property string $cover_photo
 * @property string $id
 * @property string $user_id
 * @property string $business_email
 * @property string $business_name
 * @property string $avatarUrl
 * @property string $bannerUrl
 * @property string $phone
 * @property string $street_address
 * @property string $city
 * @property string $zip_code
 * @property string $province
 * @property string $country
 * @property string $bio
 * @property string $lat
 * @property string $long
 * @property string $threads
 * @property string $instagram
 * @property string $facebook
 * @property string $twitter
 * @property string $youtube
 * @property string $spotify
 * @property string $bucket
 * @property bool $is_freeloader
 * @property float $credit_balance
 */
class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new AwsService();

        $avatar = $this->avatar;
        $cover = $this->cover_photo;

        $roles = $this->roles ? $this->roles->first()->name : '';

        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'business_email'        => $this->business_email,
            'business_name'         => $this->business_name,
            'avatar'                => $this->avatarUrl,
            'cover_photo'           => $this->bannerUrl,
            'phone'                 => $this->phone,
            'street_address'        => $this->street_address,
            'city'                  => $this->city,
            'zip_code'              => $this->zip_code,
            'province'              => $this->province,
            'country'               => $this->country,
            'bio'                   => $this->bio,
            'credit_balance'        => $this->credit_balance,
            'is_freeloader'         => $this->is_freeloader,
            'role'                  => $roles,
            'bucket'                => $this->bucket,
            // Social Media Links
            'spotify'               => $this->spotify,
            'youtube'               => $this->youtube,
            'twitter'               => $this->twitter,
            'facebook'              => $this->facebook,
            'instagram'             => $this->instagram,
            'threads'               => $this->threads,
            'lat'                   => $this->lat,
            'long'                  => $this->long,
        ];
        return parent::toArray($request);
    }
}
