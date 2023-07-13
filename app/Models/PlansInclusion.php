<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PlansInclusion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'plan_inclusions';
    protected $fillable = [
        'plan_id', 'inclusions',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'plan_id' => 'string',
        'inclusions'    => 'string',
    ];

    public function plan()
    {
        return $this->belongsToMany(Plan::class);
    }
}
