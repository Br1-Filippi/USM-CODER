<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'users';

    public function student()
    {
        return $this->hasOne(Student::class, 'id');
    }

    public function profesor()
    {
        return $this->hasOne(Profesor::class, 'id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id');
    }
}
