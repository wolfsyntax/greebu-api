<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $song_title
 * @property string $song
 */
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
        'professional_fee', 'is_hourly', 'set_played', 'song', 'song_title',
        'deactivated_at',
        'accept_request', 'accept_booking', 'accept_proposal',
    ];

    protected $appends = [
        // 'avgRating'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'profile_id'            => 'string',
        'artist_type_id'        => 'string',
        'youtube_channel'       => 'string',
        'spotify_profile'       => 'string',
        'twitter_username'      => 'string',
        'instagram_username'    => 'string',
        'professional_fee'      => 'decimal:2',
        'is_hourly'             => 'boolean',
        'set_played'            => 'integer',
        'deactivated_at'        => 'timestamp',
        'accept_request'        => 'boolean',
        'accept_booking'        => 'boolean',
        'accept_proposal'       => 'boolean',
        'song'                 => 'string',
        'song_title'            => 'string',
        // 'genres'                => 'array'
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function followers()
    {
        return $this->belongsTo(Profile::class)->with('followers');
    }

    public function artistType()
    {
        return $this->belongsTo(ArtistType::class);
    }

    /**
     * Get all of the artist proposals
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(ArtistProposal::class);
    }

    /**
     * Get all of the members for the Artist
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ArtistReview::class);
    }

    public function songRequests(): BelongsToMany
    {
        // return $this->hasMany(SongRequest::class);
        return $this->belongsToMany(SongRequest::class, 'song_request_artists', 'artist_id', 'song_request_id')->withTimestamps();
        // return $this->belongsToMany(Profile::class, 'followers', 'following_id', 'follower_id')->withTimestamps();
    }
    public function avgRating()
    {
        return $this->reviews()
            ->selectRaw('avg(star_rating) as aggregate, artist_id')
            ->groupBy('artist_id');
    }

    public function getAvgRatingAttribute()
    {
        if (!array_key_exists('avgRating', $this->relations)) {
            $this->load('avgRating');
        }

        $relation = $this->getRelation('avgRating')->first();

        return ($relation) ? $relation->aggregate : null;
    }

    /**
     * The roles that belong to the Artist
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    // public function genres(): BelongsToMany
    // {
    //     return $this->belongsToMany(Genre::class, 'artist_genres', 'artist_id', 'genre_id')->withTimestamps();
    // }

    /**
     * The roles that belong to the Artist
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function genres(): HasMany
    {
        return $this->hasMany(ArtistGenres::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'artist_communities', 'artist_id', 'communities_id')->withTimestamps();
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(SupportedLanguage::class, 'artist_languages', 'artist_id', 'language_id')->withTimestamps();
    }
}
