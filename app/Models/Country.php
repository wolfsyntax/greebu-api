<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Country extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'iso2', 'iso3', 'currency', 'symbol',
    ];

    protected $appends = [
        'phoneCode',
        'plusIso2',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name'      => 'string',
        'iso2'      => 'string',
        'iso3'      => 'string',
        'currency'  => 'string',
        'symbol'    => 'string',
    ];

    /**
     * Get Country (iso2)
     * @return string
     */
    public function getPhoneCodeAttribute(): string
    {
        return $this->name . ' (+' . $this->iso2 . ')';
    }

    /**
     * Get append + to ISO2
     * @return string
     */
    public function getPlusIso2Attribute(): string
    {
        return '+' . $this->iso2;
    }
}
