<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'cost_text', 'cost_value',
        'description', 'plan_type', 'is_active',
        'account_type',
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
        'cost_text'     => 'string',
        'cost_value'    => 'decimal:2',
        'description'   => 'string',
        'plan_type'     => 'string',
        'is_active'     => 'boolean',
        'account_type'  => 'string',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inclusions(): HasMany
    {
        return $this->hasMany(PlansInclusion::class);
    }
}
