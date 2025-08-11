<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfesorCourse extends Pivot
{
    use SoftDeletes;

    protected $table = 'profesor_course';
    public $timestamps = false;
}
