<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Libraries\AwsService;

/**
 * @property \App\Models\Profile $profile
 * @property string $id
 * @property string $creator_id
 * @property string $content
 * @property string $attachment
 * @property string $attachment_type
 * @property string $longitude
 * @property string $latitude
 * @property bool $is_schedule
 * @property string $scheduled_at
 * @property object $created_at
 * @property object $updated_at
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load('profile');
        $profile = $this->profile;

        // $service = new AwsService();

        $avatar = $this->profile->avatarUrl;

        // if (!$profile->avatar) {
        //     $avatar = 'https://ui-avatars.com/api/?name=' . $profile->business_name . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        // } else {
        //     $avatar_host = parse_url($avatar);
        //     if (!array_key_exists('host', $avatar_host)) {
        //         $avatar = $service->get_aws_object($avatar);
        //     }
        // }

        return [
            'id'                => $this->id,
            'avatar'            => $avatar,
            'creator'           => $profile->business_name,
            'creator_id'        => $this->creator_id,
            'content'           => $this->content,
            'attachment'        => $this->attachment,
            'attachment_type'   => $this->attachment_type,
            'longitude'         => $this->longitude,
            'latitude'          => $this->latitude,
            'is_schedule'       => $this->is_schedule,
            'scheduled_at'      => $this->scheduled_at,
            'createdAt'         => $this->created_at->diffForHumans(),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
