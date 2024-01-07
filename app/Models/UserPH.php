<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $first_name
 * @property string $last_name
 */
class UserPH extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /** @var string */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'last_login',
        'google_id', 'facebook_id', 'email_verified_at',
        'remember_token',
        'last_login',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'fullname',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'id' => 'string',
        'last_login' => 'datetime',
    ];

    /**
     * Get Full name
     * @return string
     */
    public function getFullnameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function setPasswordAttribute(string $value): string
    {
        return $this->attributes['password'] = hash('sha256', $value, false);
    }
}
