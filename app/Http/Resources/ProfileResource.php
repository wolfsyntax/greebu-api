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
        $avatar = $this->avatar ? Storage::disk($this->bucket)->temporaryUrl($this->avatar, now()->addMinutes(60)) : '';
        $roles = $this->roles ? $this->roles->first()->name : '';
        return [
            'user_id'           => $this->user_id,
            'avatar'            => $avatar,
            'phone'             => $this->phone,
            'street_address'    => $this->street_address,
            'city'              => $this->city,
            'zip_code'          => $this->zip_code,
            'province'          => $this->province,
            'country'           => $this->country,
            'bio'               => $this->bio,
            'credit_balance'    => $this->credit_balance,
            'role'              => $roles,
        ];
        return parent::toArray($request);
    }
}
