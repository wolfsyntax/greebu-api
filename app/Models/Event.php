<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $barangay
 * @property string $city
 * @property string $province
 * @property string $street_address
 */
class Event extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'organizer_id',
        'profile_id',
        'event_type',
        'cover_photo', 'event_name', 'venue_name',
        // 'location',
        'street_address', 'barangay', 'city', 'province',
        'audience', 'start_date', 'end_date',
        'start_time', 'end_time', 'description',
        'lat', 'long',
        'capacity',
        'is_featured', 'is_free',
        'status', 'review_status',
        // What are you looking for?
        'look_for', /*'look_type',*/ 'requirement',
        'reason', 'deleted_at',
        'total_participants',
    ];

    /**
     * @var array<int,string>
     */
    protected $appends = [
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reason'            => 'string',
        'total_participants'    => 'integer',
        // 'organizer_id'      => 'string',
        'profile_id'      => 'string',
        'event_type'        => 'string',
        'cover_photo'       => 'string',
        'event_name'        => 'string',
        // 'location'             => 'string',
        'audience'          => 'boolean',
        'start_date'        => 'datetime:Y-m-d',
        'end_date'          => 'datetime:Y-m-d',
        'start_time'        => 'datetime:H:i:s',
        'end_time'          => 'datetime:H:i:s',
        'description'       => 'string',
        'lat'               => 'string',
        'long'              => 'string',
        'capacity'          => 'integer',
        'is_featured'       => 'boolean',
        'is_free'           => 'boolean',
        'status'            => 'string',
        'review_status'     => 'string',
        'look_for'          => 'string',
        // 'look_type'         => 'string',
        'requirement'       => 'string',
        'street_address'    => 'string',
        'barangay'          => 'string',
        'city'              => 'string',
        'province'          => 'string',
        'venue_name'        => 'string',
    ];


    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_participants'    => 0,
        'reason'            => '',
        // 'cover_photo'       => '',
        'event_name'        => '',
        'event_type'        => '',
        'venue_name'        => '',
        // 'location'          => '',
        'audience'          => true,
        'description'       => '',
        'lat'               => '0.00',
        'long'              => '0.00',
        'capacity'          => 0,
        'is_featured'       => false,
        'is_free'           => false,
        'status'            => 'draft',
        'review_status'     => 'accepted',
        'look_for'          => '',
        // 'look_type'         => '',
        'requirement'      => '',
        'street_address'    => 'string',
        'barangay'          => 'string',
        'city'              => 'string',
        'province'          => 'string',

    ];

    /**
     * Get Location
     * @return string
     */
    public function getLocationAttribute(): string
    {
        return $this->street_address . ', ' . $this->barangay . ', ' . $this->city . ', ' . $this->province;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(ArtistProposal::class);
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function artistProposals(): HasMany
    {
        return $this->hasMany(ArtistProposal::class)->accepted();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lookTypes(): HasMany
    {
        return $this->hasMany(LookType::class);
    }
}
