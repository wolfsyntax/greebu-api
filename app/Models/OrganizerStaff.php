<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Support\Str;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $gender
 * @property string $avatar
 */
class OrganizerStaff extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organizer_id',
        'first_name', 'last_name', 'gender',
        'avatar', 'role', 'email', 'phone',
        'facebook_profile', 'birthdate', 'hired_since',
        'deactivated_at',
    ];

    /**
     * @var array<int,string>
     */
    protected $appends = [
        'avatar_text', 'fullname',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [];

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
