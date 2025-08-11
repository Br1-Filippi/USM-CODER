<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Career extends Model
{
    use SoftDeletes;

    protected $table = 'carrers';

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function profesors()
    {
        return $this->hasMany(Profesor::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
