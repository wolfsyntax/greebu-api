<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ArtistProposal extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id', 'artist_id', 'total_member', 'cover_letter', 'accepted_at', 'declined_at', 'cancelled_at', 'cancel_reason',
    ];

    protected $appends = [];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function scopeFilterBy($query, $filter)
    {
        return $query->where('status', $filter);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($query) {
            if ($query->status === 'accepted') {
                $query->accepted_at = now();
            } else if ($query->status === 'declined') {
                $query->declined_at = now();
            }
        });
    }
}
