<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $table = 'questions';

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function uniTest()
    {
        return $this->hasOne(UniTest::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
