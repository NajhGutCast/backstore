<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    public $timestamps = false;

    public function permisos()
    {
        return $this->hasMany(Permiso::class, '_vista', 'id');
    }
}
