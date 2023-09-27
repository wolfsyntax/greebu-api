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
        'location', 'audience', 'start_date', 'end_date',
        'start_time', 'end_time', 'description',
        'lat', 'long',
        'capacity',
        'is_featured', 'is_free',
        'status', 'review_status',
        // What are you looking for?
        'look_for', 'look_type', 'requirements',
    ];

    protected $appends = [];

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
        'venue'             => 'string',
        'is_public'         => 'boolean',
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
        'requirements'      => 'string',
    ];

    protected $attributes = [
        // 'cover_photo'       => '',
        'event_name'        => '',
        'venue'             => '',
        'is_public'         => true,
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
        'requirements'      => '',
    ];

    public static function create(array $attributes)
    {
        $attributes['start_date']       = \Carbon\Carbon::now()->addDays(5);
        $attributes['end_date']         = \Carbon\Carbon::now()->addDays(15);
        $attributes['start_time']       = \Carbon\Carbon::now()->toTimeString();
        $attributes['end_time']         = \Carbon\Carbon::now()->addHours(5)->toTimeString();

        return parent::create($attributes);
    }
}
