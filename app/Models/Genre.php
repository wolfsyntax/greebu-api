<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Genre extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title', 'description',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title'         => 'string',
        'description'   => 'string',
    ];

    public function albums()
    {
        return $this->belongsToMany(Album::class)->withTimestamps();
    }

    // public function artists()
    // {
    //     return $this->belongsToMany(Artist::class, 'artist_genres', 'genre_id', 'artist_id');
    // }
}
