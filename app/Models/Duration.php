<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property int $value
 * @property string $duration_type
 * @property string $first_name
 * @property string $last_name
 */
class Duration extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title', 'value', 'cost', 'duration_type',
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
        'title'         => 'string',
        'value'         => 'integer',
        'cost'          => 'decimal:2',
        'duration_type' => 'string',
    ];

    /**
     * Get Duration with minutes
     * @return string
     */
    public function getDurationAttribute(): string
    {
        $duration = $this->value;
        if ($this->duration_type === 'hour') {
        }
        return $this->first_name . ' ' . $this->last_name;
    }
}
