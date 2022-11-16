<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public $timestamps = false;

    public function categoria()
    {
        return $this->hasOne(Categoria::class, 'id', '_categoria');
    }

    public function medida()
    {
        return $this->hasOne(Medida::class, 'id', '_medida');
    }
}
