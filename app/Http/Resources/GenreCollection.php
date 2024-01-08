<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GenreCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return \App\Models\Genre
     */
    public function toArray(Request $request): mixed
    {
        return $this->collection;
    }
}
