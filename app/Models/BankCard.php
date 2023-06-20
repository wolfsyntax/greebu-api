<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BankCard extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    protected $table = 'card_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'card_owner', 'card_num', 'card_cvv',
        'brand', 'exp_month', 'exp_year',
        'profile_id',
    ];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'card_owner'    => 'string',
        'card_num'      => 'string',
        'card_cvv'      => 'string',
        'brand'         => 'string',
        'exp_month'     => 'string',
        'exp_year'      => 'string',
        'profile_id'    => 'string',
    ];
}
