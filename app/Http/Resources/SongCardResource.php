<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Libraries\AwsService;

class SongCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $service = new AwsService();
        $avatar = $this->creator->avatar;

        if (!$avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->creator->business_name, '', 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($avatar);
            }
        }
        return [
            'user_story'                => $this->user_story ?? '',
            'song_request_id'                => $this->id ?? '',
            'purpose'                   => $this->purpose->name ?? '',
            'mood'                      => $this->mood->name ?? '',
            // 'genre'
            'language'                  => $this->language->name ?? '',
            'creator'                   => [
                'name'                  => $this->creator->business_name,
                'email'                 => $this->creator->business_email,
                'avatar'                => $avatar,
            ],
            'duration'                  => $this->duration->title ?? '',
            'sender'                    => $this->sender,
            'receiver'                  => $this->receiver,
            'purpose'                   => $this->purpose->name ?? '',
            'first_name'                => $this->first_name,
            'last_name'                 => $this->last_name,
            'email'                     => $this->email,
            'page_status'               => $this->page_status,
            'verification_status'       => $this->verification_status,
            'delivery_date'             => $this->delivery_date,
            'estimate_date'             => $this->estimate_date,
            'approved_at'               => $this->approved_at,
            'approval_status'           => $this->approval_status,
            'artists'                   => new ArtistCollection($this->artists),
            'created_at'                => $this->created_at,
        ];
        return parent::toArray($request);
    }
}
