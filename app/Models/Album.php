<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Album extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    // public $incrementing = false;
    // protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'artist_id', 'title', 'album_cover',
        'producer', 'released_at',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title'         => 'string',
        'artist_id'     => 'string',
        'album_cover'   => 'string',
        'producer'      => 'string',
        'released_at'   => 'datetime',
    ];

    public function tracks()
    {
        return $this->hasMany(Track::class)->withTimestamps();
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }
}
