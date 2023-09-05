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

        $avatar_host = parse_url($avatar)['host'] ?? '';
        $cover_host = parse_url($cover)['host'] ?? '';

        if ($avatar) {

            if ($avatar_host) {
            } else {
                $avatar = $service->get_aws_object($avatar, false);
            }
        }

        if ($cover) {

            if ($cover_host) {
            } else {
                $cover = $service->get_aws_object($cover, false);
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
            'bucket'             => $this->bucket,
            'avatar_host'        => $avatar_host,
            'cover_host'         => $cover_host,
        ];
        return parent::toArray($request);
    }
}
