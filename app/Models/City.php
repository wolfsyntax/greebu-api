<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class City extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'ascii', 'lat', 'lng',
        'province', 'country',
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
        'ascii'         => 'string',
        'lat'           => 'string',
        'lng'           => 'string',
        'province'      => 'string',
        'country'       => 'string',
    ];

    /**
     * @var array<string,mixed>
     */
    protected $attributes = [
        'ascii'         => '',
        'lat'           => '0.0000000',
        'lng'           => '0.0000000',
        'province'      => 'Camarines Sur',
        'country'       => 'Philippines',
    ];
}
