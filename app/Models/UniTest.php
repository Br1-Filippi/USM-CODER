<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniTest extends Model
{
    use SoftDeletes;

    protected $table = 'uni_test';

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
