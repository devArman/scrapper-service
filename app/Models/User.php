<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Http\UploadedFile;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use SoftDeletes, Authenticatable, Authorizable, HasFactory;

    protected $casts = [
        'owner' => 'boolean',
    ];

    public function researches(){
        return $this->hasMany('App\Models\Research', 'user_id', 'id');

    }

}
