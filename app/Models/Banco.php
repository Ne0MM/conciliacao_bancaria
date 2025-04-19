<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;

    protected $table = 'bancos';
    
    protected $fillable = [
        'codigo',
        'nome'
    ];

    protected $casts = [
        'codigo' => 'string',
        'nome' => 'string'
    ];
    
    // Retorna todos os bancos com o id e nome
    public static function getAllBancos()
    {
        return self::all()->pluck('nome', 'id')->toArray();
    }
} 