<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
// use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasUuids;

    // public $incrementing = false;
    // protected $keyType = 'string';


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
        //'password' => 'hashed',
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

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }


    // Alternative for UUIDs
    // public static function boot()
    // {

    //     parent::boot();

    //     static::creating(function ($query) {
    //         $query->id = Str::uuid()->toString();
    //     });

    //     static::saving(function ($query) {
    //     });
    // }
}
