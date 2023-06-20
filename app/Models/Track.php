<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Track extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'album_id', 'language_id', 'genre_id',
        'title', 'duration', 'is_playable', 'file_path',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'album_id'      => 'string',
        'language_id'   => 'string',
        'genre_id'      => 'string',
        'title'         => 'string',
        'duration'      => 'integer',
        'is_playable'   => 'boolean',
        'file_path'     => 'string',
    ];

    public function albums()
    {
        return $this->belongsToMany(Album::class)->withTimestamps();
    }
}
