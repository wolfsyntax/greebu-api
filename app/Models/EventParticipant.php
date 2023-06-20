<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EventParticipant extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    // custom created_at column name
    const CREATED_AT = 'booked_at';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_pricing_id',
        'full_name', 'email', 'phone',
        'is_paid', 'id_num', 'id_type',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_pricing_id'  => 'string',
        'full_name'         => 'string',
        'email'             => 'string',
        'phone'             => 'string',
        'is_paid'           => 'string',
        'id_num'            => 'string',
        'id_type'           => 'string',
    ];
}
