<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'profile_id', 'artist_type_id',
        'youtube_channel', 'spotify_profile', 'twitter_username', 'instagram_username',
        'professional_fee', 'is_hourly', 'set_played',
        'deactivated_at',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'profile_id'        => 'string',
        'artist_type_id'    => 'string',
        'youtube_channel'   => 'string',
        'spotify_profile'   => 'string',
        'twitter_username'  => 'string',
        'instagram_username' => 'string',
        'professional_fee'  => 'decimal:2',
        'is_hourly'         => 'boolean',
        'set_played'        => 'integer',
        'deactivated_at'    => 'timestamp',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function artistType()
    {
        return $this->belongsTo(ArtistType::class);
    }
    /**
     * Get all of the members for the Artist
     *
     * @return \Illuminate\DatabMemberquent\Relations\HasMany
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
    /**
     * The roles that belong to the Artist
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'artist_genres', 'artist_id', 'genre_id')->withTimestamps();
    }
}
