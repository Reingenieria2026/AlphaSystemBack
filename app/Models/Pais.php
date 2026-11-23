<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;

    protected $table = 'pais';
    protected $primaryKey = 'idPais';
    protected $fillable = ['nombrePais'];
    public function estados()
    {
        return $this->hasMany(Estado::class, 'idPais');
    }
}
