<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    public $timestamps = false;

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, '_rol', 'id');
    }
}
