<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

use App\Libraries\AwsService;

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
        // $avatar = filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : ($this->bucket === 's3' ? Storage::disk($this->bucket)->url($this->avatar) : ($this->avatar ? Storage::disk($this->bucket)->temporaryUrl($this->avatar, now()->addMinutes(60)) : ''));

        $avatar = $this->avatar;
        $cover = $this->cover_photo;

        if ($this->bucket && in_array($this->bucket, ['s3', 's3priv',])) {
            if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
                $avatar = $service->get_aws_object($avatar, $this->bucket === 's3priv');
            }

            if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
                $cover = $service->get_aws_object($cover, false);
            } else {
                $cover = '';
            }
        }

        $roles = $this->roles ? $this->roles->first()->name : '';

        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'business_email'    => $this->business_email,
            'business_name'     => $this->business_name,
            'avatar'            => $avatar ?? '',
            'ax'                => $this->avatar,
            'cp'                => $this->cover_photo,
            'cover_photo'       => $cover ?? '',
            'phone'             => $this->phone,
            'street_address'    => $this->street_address,
            'city'              => $this->city,
            'zip_code'          => $this->zip_code,
            'province'          => $this->province,
            'country'           => $this->country,
            'bio'               => $this->bio,
            'credit_balance'    => $this->credit_balance,
            'is_freeloader'     => $this->is_freeloader,
            'role'              => $roles,
            'bucket'            => $this->bucket,
        ];
        return parent::toArray($request);
    }
}
