<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrganizerEventTypes extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'organizer_event_types';

    protected $fillable = [
        'organizer_id', 'event_type',
    ];
}
