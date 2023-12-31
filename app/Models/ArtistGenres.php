<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ArtistGenres extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'artist_genres';

    /**
     * @var array<int,string>
     */
    protected $fillable = [
        'artist_id', 'genre_title',
    ];
}
