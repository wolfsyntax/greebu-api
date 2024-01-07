<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EventType extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    // public $incrementing = false;
    // protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'description',
    ];

    /**
     * @var array<int,string>
     */
    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name'          => 'string',
        'description'   => 'string',
    ];

    /**
     * @return string
     */
    public function setNameAttribute(string $value): string
    {
        return $this->attributes['name'] = strtolower($value);
    }
}
