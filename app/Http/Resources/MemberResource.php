<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : Storage::disk('s3')->url($this->avatar);

        return [
            'member_name' => $this->fullname,
            'avatar' => $avatar,
            'role' => ucfirst($this->role),
        ];
        // return parent::toArray($request);
    }
}
