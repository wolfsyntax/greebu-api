<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
// use Spatie\Permission\Traits\HasRoles;
use App\Traits\TwilioTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;

/**
 * @property string $email
 * @property string $fullname
 * @property string $phone
 * @property string $first_name
 * @property string $last_name
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasUuids, TwilioTrait;

    // public $incrementing = false;
    // protected $keyType = 'string';
    protected string $guard_name = 'api';

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
        'phone_verified_at',
    ];

    protected $appends = [
        'fullname', 'phonemask', 'emailmask',
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
     * @return bool
     */
    public function sendCode()
    {
        return $this->sendOTP($this->phone);
    }

    /**
     * @return bool
     */
    public function verifyCode($code)
    {
        return $this->verifyOTP($this->phone, $code);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
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

    /**
     * Get Phone mask
     * @return string
     */
    public function getPhonemaskAttribute(): string
    {
        return Str::of($this->phone)->mask('*', (Str::startsWith($this->phone, '+') ? 4 : 3), -4);
    }

    /**
     * Get Email masking
     * @return string
     */
    public function getEmailmaskAttribute(): string
    {
        return Str::of($this->email)->mask('*', 3, -5);
    }

    public function setPasswordAttribute(string $value): string
    {
        return $this->attributes['password'] = hash('sha256', $value, false);
    }

    /**
     * @return \App\Models\Profile
     */
    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\EmailVerification($this));
    }
}
