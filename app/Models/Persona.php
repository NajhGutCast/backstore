<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    public $timestamps = false;

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, '_persona', 'id');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, '_persona', 'id');
    }

    public function proveedor()
    {
        return $this->hasOne(Proveedor::class, '_persona', 'id');
    }
}
