<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organizer extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'profile_id',
        'first_name', 'last_name',
        'email', 'phone_alt', 'facebook_url',
        'bio', 'banned_at',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'profile_id'    => 'string',
        'first_name'    => 'string',
        'last_name'     => 'string',
        'email'         => 'string',
        'phone_alt'     => 'string',
        'facebook_url'  => 'string',
        'bio'           => 'string',
        'banned_at'     => 'timestamp',
    ];
}
