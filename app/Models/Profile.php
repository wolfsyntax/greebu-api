<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Libraries\AwsService;

class Profile extends Model
{
    use HasFactory, HasRoles, SoftDeletes, HasUuids, Notifiable;

    protected $guard_name = 'web';
    const DELETED_AT = 'deactivated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'business_email', 'business_name', 'phone',
        'account_type', 'credit_balance',
        'avatar', 'cover_photo', 'bio',
        'street_address', 'city', 'zip_code', 'province', 'country',
        'last_accessed', 'bucket',
        // social media profile
        'youtube', 'spotify', 'twitter', 'instagram', 'facebook', 'threads',
        'personal_code',
    ];

    protected $appends = ['avatarUrl', 'bannerUrl',];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'           => 'string',
        'business_email'    => 'string',
        'business_name'     => 'string',
        'phone'             => 'string',
        'account_type'      => 'string',
        'credit_balance'    => 'decimal:2',
        'avatar'            => 'string',
        'cover_photo'       => 'string',
        'bio'               => 'string',
        'last_accessed'     => 'timestamp',
        'bucket'            => 'string',
        // social media profile
        'youtube'           => 'string',
        'spotify'           => 'string',
        'twitter'           => 'string',
        'instagram'         => 'string',
        'facebook'          => 'string',
        'threads'          => 'string',
    ];

    protected $attributes = [
        'business_name'     => '',
        'business_email'    => '',
        'street_address'    => '',
        'city'              => '',
        'zip_code'          => '',
        'province'          => '',
        // social media profile
        'youtube'           => '',
        'spotify'           => '',
        'twitter'           => '',
        'instagram'         => '',
        'facebook'          => '',
        // 'country'           => 'Philippines',
    ];

    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->personal_code = uniqid();
        });
    }

    public function artist()
    {
        return $this->hasOne(Artist::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function organizer()
    {
        return $this->hasOne(Organizer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function providers()
    {
        return $this->hasOne(ServiceProvider::class);
    }

    public function followers()
    {
        return $this->belongsToMany(Profile::class, 'followers', 'following_id', 'follower_id')->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(Profile::class, 'followers', 'follower_id', 'following_id')->withTimestamps();
    }

    /**
     * Get all of the events created by Organizer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function scopeAccount($query, string $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        });
    }

    public function scopeMyAccount($query, string $role)
    {
        return $query->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        });
    }

    /**
     * Get Full name
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        $service = new AwsService();
        $avatar = $this->avatar;

        if (!$avatar) {
            $avatar = 'https://ui-avatars.com/api/?name=' . substr($this->business_name, 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        } else {
            $avatar_host = parse_url($avatar);
            if (!array_key_exists('host', $avatar_host)) {
                $avatar = $service->get_aws_object($avatar);
            }
        }

        return $avatar;
    }

    /**
     * Get Full name
     * @return string
     */
    public function getBannerUrlAttribute(): string
    {
        $service = new AwsService();
        $banner = $this->cover_photo ?? '';

        if ($banner) {
            $banner_host = parse_url($banner);
            if (!array_key_exists('host', $banner_host)) {
                $banner = $service->get_aws_object($banner);
            }
        }

        return $banner;
    }
}
