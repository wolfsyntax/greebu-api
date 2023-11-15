<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Libraries\AwsService;

class MemberResource extends JsonResource
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
        if (!$this->avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->first_name, '', 0) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($this->avatar);
            }
        }

        return [
            'id'            => $this->id,
            'band_id'       => $this->artist_id,
            'member_name'   => $this->fullname,
            'avatar'        => $avatar,
            'role' => ucfirst($this->role),
        ];
        // return parent::toArray($request);
    }
}
