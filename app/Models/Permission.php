<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as BasePermission;
//use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Permission extends BasePermission
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
