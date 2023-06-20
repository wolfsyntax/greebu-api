<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Event extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organizer_id', 'artist_id',
        'title', 'description',
        'venue', 'lat', 'long', 'capacity',
        'event_date', 'start_time', 'duration',
        'is_public', 'is_featured', 'status',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'organizer_id'  => 'string',
        'artist_id'     => 'string',
        'title'         => 'string',
        'description'   => 'string',
        'venue'         => 'string',
        'lat'           => 'string',
        'long'          => 'string',
        'capacity'      => 'integer',
        'event_date'    => 'date',
        // 'start_time'    => '',
        'duration'      => 'integer',
        'is_public'     => 'boolean',
        'is_featured'   => 'boolean',
        'status'        => 'string',
    ];
}
