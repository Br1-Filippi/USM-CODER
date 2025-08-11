<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $table = 'courses';

    public function career()
    {
        return $this->belongsTo(Career::class, 'carrer');
    }

    public function profesors()
    {
        return $this->belongsToMany(Profesor::class, 'profesor_course');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_course');
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }
}
