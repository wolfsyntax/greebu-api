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

        // $avatar_host = parse_url($avatar)['host'] ?? '';
        // $cover_host = parse_url($cover)['host'] ?? '';

        // if ($avatar) {

        //     if ($avatar_host) {
        //     } else {
        //         $avatar = $service->get_aws_object($avatar, false);
        //     }
        // }

        // if ($cover) {

        //     if ($cover_host) {
        //     } else {
        //         $cover = $service->get_aws_object($cover, false);
        //     }
        // }

        if (!$this->avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($this->avatar);
            }
        }

        if (!$this->cover_photo) {
            // $cover = 'https://ui-avatars.com/api/?name=' . substr($this->business_name,  0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $cover_host = parse_url($cover);
            if (!array_key_exists('host', $cover_host)) {
                $cover = $service->get_aws_object($this->cover_photo);
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
            // Social Media Links
            'spotify'               => $this->spotify,
            'youtube'               => $this->youtube,
            'twitter'               => $this->twitter,
            'instagram'             => $this->instagram,
        ];
        return parent::toArray($request);
    }
}
