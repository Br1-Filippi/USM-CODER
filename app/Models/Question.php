<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $table = 'questions';

    public $timestamps = false;

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function unitests()
    {
        return $this->hasMany(UniTest::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
    
    public function language()
    {
        return $this->belongsTo(Language::class, 'lenguaje_id');
    }
}
