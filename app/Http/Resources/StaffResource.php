<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Libraries\AwsService;

class StaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new AwsService();
        $data = $request;
        $avatar = $this->avatar;

        if (!$this->avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->first_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($this->avatar);
            }
        }

        $data['avatar'] = $avatar;

        return parent::toArray($data);
        // return [
        //     'id'            => $this->id,
        //     'organizer_id'       => $this->organizer_id,
        //     'member_name'   => $this->fullname,
        //     'avatar'        => $avatar,
        //     'role' => ucfirst($this->role),
        // ];

        // return parent::toArray($request);
    }
}
