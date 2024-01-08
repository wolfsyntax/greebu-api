<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $first_name
 * @property string $last_name
 */
class Member extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'artist_id', 'avatar', 'role',
        'first_name', 'last_name', 'gender',
        'email', 'phone', 'facebook_profile',
        'birthdate', 'member_since', 'deactivated_at',
    ];

    /**
     * @var array<int,string>
     */
    protected $appends = ['avatar_text', 'fullname',];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'artist_id'         => 'string',
        'first_name'        => 'string',
        'last_name'         => 'string',
        'gender'            => 'string',
        'email'             => 'string',
        'phone'             => 'string',
        'facebook_profile'  => 'string',
        'birthdate'         => 'date',
        'member_since'      => 'date',
        'deactivated_at'    => 'timestamp',
        'avatar'            => 'string',
        'role'              => 'string',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function team(): HasOne
    {
        return $this->hasOne(Artist::class);
    }

    /**
     * Get Avatar Text
     * @return string
     */
    public function getAvatarTextAttribute(): string
    {
        $fname = $this->last_name ? $this->first_name . ' ' . $this->last_name : $this->first_name;

        $words = Str::of(Str::words($fname, 2, ''))->split('/[\s]/');
        $text = '';
        foreach ($words as $word) {
            $text .= $word[0];
        }

        return $text;
    }

    /**
     * Get Avatar Text
     * @return string
     */
    public function getFullnameAttribute(): string
    {
        $fname = $this->last_name ? $this->first_name . ' ' . $this->last_name : $this->first_name;
        return $fname;
    }
}
