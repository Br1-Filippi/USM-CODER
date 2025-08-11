<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model
{
    use SoftDeletes;

    protected $table = 'admin';

    protected $primaryKey = 'id';
    public $incrementing = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
