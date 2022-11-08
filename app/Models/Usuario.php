<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    public $timestamps = false;

    public function rol()
    {
        return $this->hasOne(Rol::class, 'id', '_rol');
    }

    public function persona()
    {
        return $this->hasOne(Persona::class, 'id', '_persona');
    }
}
