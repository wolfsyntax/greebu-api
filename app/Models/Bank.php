<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Bank extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'short_name',
    ];

    protected $appends = ['bank'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name'          => 'string',
        'short_name'    => 'string',
    ];

    /**
     * Get Bank name and Short name
     * i.e. Asia United Bank (AUB)
     * @return string
     */
    public function getBankAttribute(): string
    {

        return $this->name . ($this->short_name ? ' (' . $this->short_name . ')' : '');
    }
}
