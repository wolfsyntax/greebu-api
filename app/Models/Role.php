<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as BaseRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Role extends BaseRole
{
    use HasFactory; //, HasUuids;

    // public $incrementing = false;
    // protected $keyType = 'string';

    // public static function boot()
    // {

    //     parent::boot();

    //     static::creating(function ($query) {
    //         $query->id = Str::uuid()->toString();
    //     });

    //     static::saving(function ($query) {
    //     });
    // }
}
