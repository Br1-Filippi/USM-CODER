<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';

    protected $primaryKey = 'id';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function career()
    {
        return $this->belongsTo(Career::class, 'career_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'student_course');
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
