<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Support\Str;
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
        'organizer_id', 'event_types_id',
        'cover_photo', 'event_name',
        // 'location',
        'street_address', 'barangay', 'city', 'province',
        'audience', 'start_date', 'end_date',
        'start_time', 'end_time', 'description',
        'lat', 'long',
        'capacity',
        'is_featured', 'is_free',
        'status', 'review_status',
        // What are you looking for?
        'look_for', 'look_type', 'requirement',
    ];

    protected $appends = [
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'organizer_id'      => 'string',
        'event_types_id'    => 'string',
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
        'look_type'         => 'string',
        'requirement'       => 'string',
        'street_address'    => 'string',
        'barangay'          => 'string',
        'city'              => 'string',
        'province'          => 'string',
    ];

    protected $attributes = [
        // 'cover_photo'       => '',
        'event_name'        => '',
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
        'look_type'         => '',
        'requirement'      => '',
        'street_address'    => 'string',
        'barangay'          => 'string',
        'city'              => 'string',
        'province'          => 'string',

    ];

    /**
     * Get Email masking
     * @return string
     */
    public function getLocationAttribute(): string
    {
        return $this->street_address . ',' . $this->barangay . ',' . $this->city . ',' . $this->province;
    }

    public function eventType()
    {
        return $this->hasOne(EventType::class);
    }
}
