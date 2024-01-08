<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArtistTypeCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return \App\Models\ArtistType
     */
    public function toArray(Request $request): mixed
    {
        return $this->collection;
    }
}
