<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $title
 */
class GenreProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            $this->title,
        ];
    }
}
