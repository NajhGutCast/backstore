<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    public $timestamps = false;

    public function vista()
    {
        return $this->hasOne(Vista::class, 'id', '_vista');
    }
}
