<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentCourse extends Pivot
{
    use SoftDeletes;

    protected $table = 'student_course';
    public $timestamps = false;
}
