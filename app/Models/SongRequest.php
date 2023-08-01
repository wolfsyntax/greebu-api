<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SongRequest extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_id',
        'artist_type_id', 'genre_id', 'song_type_id', 'language_id',
        'duration_id', 'purpose_id',
        'first_name', 'last_name', 'email',
        'page_status', 'approval_status',
        'sender', 'receiver',
        'user_story', 'estimate_date',
        'delivery_date', 'approved_at',
        // 'request_status',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'creator_id'            => 'string',
        // 'artist_type_id'        => 'string',
        // 'genre_id'              => 'string',
        'song_type_id'          => 'string',
        'language_id'           => 'string',
        'duration_id'           => 'string',
        'purpose_id'            => 'string',
        'first_name'            => 'string',
        'last_name'             => 'string',
        'email'                 => 'string',
        'request_status'        => 'string',
        'page_status'           => 'string',
        // 'approval_status'       => '',
        'sender'                => 'string',
        'receiver'              => 'string',
        'user_story'            => 'string',
        'delivery_date'         => 'timestamp',
        'approved_at'           => 'timestamp',
        'estimate_date'         => 'integer',
    ];

    // public function artists()
    // {
    //     return $this->hasMany(Artist::class);
    // }
    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'song_request_artists',  'song_request_id', 'artist_id')->withTimestamps();
        // return $this->belongsToMany(Genre::class, 'artist_genres', 'artist_id', 'genre_id')->withTimestamps();
    }

    public function language()
    {
        return $this->belongsTo(SupportedLanguage::class, 'language_id', 'id');
    }

    public function mood()
    {
        return $this->belongsTo(SongType::class, 'song_type_id');
    }

    public function duration()
    {
        return $this->belongsTo(Duration::class);
    }

    public function purpose()
    {
        return $this->belongsTo(Purpose::class);
    }
}
