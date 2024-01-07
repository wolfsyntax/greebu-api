<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $first_name
 * @property string $last_name
 */
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
        // 'artist_type_id',
        // 'genre',
        'song_type_id', 'language_id',
        'duration_id', 'purpose_id',
        'first_name', 'last_name', 'email',
        'page_status', 'approval_status', 'approved_at',
        'sender', 'receiver',
        'user_story', 'estimate_date',
        'delivery_date', 'approved_at', 'verification_status',
        // 'request_status',
    ];

    /**
     * @var array<int,string>
     */
    protected $appends = ['clientInfo',];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'creator_id'            => 'string',
        // 'artist_type_id'        => 'string',
        // 'genre'                 => 'string',
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'song_request_artists',  'song_request_id', 'artist_id')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(SupportedLanguage::class, 'language_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mood(): BelongsTo
    {
        return $this->belongsTo(SongType::class, 'song_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function duration(): BelongsTo
    {
        return $this->belongsTo(Duration::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purpose(): BelongsTo
    {
        return $this->belongsTo(Purpose::class);
    }

    /**
     * Get Client name
     * @return string
     */
    public function getClientInfoAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
