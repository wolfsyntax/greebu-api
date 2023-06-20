<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ArtistReview extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reviewer_id', 'artist_id', 'comments',
        'start_rating', 'status',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reviewer_id'   => 'string',
        'artist_id'     => 'string',
        'comments'      => 'string',
        'start_rating'  => 'integer',
        'status'        => 'string',
    ];
}
