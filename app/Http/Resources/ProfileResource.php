<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        // $avatar = filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : ($this->bucket === 's3' ? Storage::disk($this->bucket)->url($this->avatar) : ($this->avatar ? Storage::disk($this->bucket)->temporaryUrl($this->avatar, now()->addMinutes(60)) : ''));

        $avatar = $this->avatar;
        $cover = $this->cover_photo;

        if ($this->bucket && in_array($this->bucket, ['s3', 's3priv',])) {
            if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
                $pic = Storage::disk($this->bucket);
                $avatar = $this->bucket === 's3' ? $pic->url($avatar) : $pic->temporaryUrl($avatar, now()->addMinutes(60));
            }

            if ($cover && !filter_var($cover, FILTER_VALIDATE_URL)) {
                $pic = Storage::disk($this->bucket);

                $cover = $this->bucket === 's3' ? $pic->url($cover) : $pic->temporaryUrl($cover, now()->addMinutes(60));
            }
        }

        $roles = $this->roles ? $this->roles->first()->name : '';

        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'business_email'    => $this->business_email,
            'business_name'     => $this->business_name,
            'avatar'            => $avatar ?? '',
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
        ];
        return parent::toArray($request);
    }
}
