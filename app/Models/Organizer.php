<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'accept_proposal', 'send_proposal',
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

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * The roles that belong to the Artist
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventTypes(): HasMany
    {
        return $this->hasMany(OrganizerEventTypes::class);
    }

    /**
     * Get all of the staffs for the Organizer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staffs(): HasMany
    {
        return $this->hasMany(OrganizerStaff::class);
    }
}
