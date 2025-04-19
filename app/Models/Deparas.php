<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deparas extends Model
{
    protected $table = 'deparas';

    protected $fillable = [
        'descricao_bancaria',
        'categoria',
    ];

    protected $casts = [
        'descricao_bancaria' => 'string',
        'categoria' => 'string',
    ];
    public $timestamps = true;

    // Checa se o histórico já existe na tabela de deparas
    static public function checkForExistingDeparas($historico)
    {
        $datePattern = '/\d{2}\/\d{2}\s\d{2}:\d{2}/';

        $baseHistorico = preg_replace($datePattern, '', $historico);

        return self::where('descricao_bancaria', $baseHistorico)
            ->first();
    }

    // Cria um novo depara com o histórico sem data
    static public function createDeparasWithNormalizedHistorico($historico, $categoria)
    {

        $datePattern = '/\d{2}\/\d{2}\s\d{2}:\d{2}/';

        $baseHistorico = preg_replace($datePattern, '', $historico);

        self::create([
            'descricao_bancaria' => $baseHistorico,
            'categoria' => $categoria,
        ]);
    }

    static public function removeDepara($historico)
    {
        $datePattern = '/\d{2}\/\d{2}\s\d{2}:\d{2}/';

        $baseHistorico = preg_replace($datePattern, '', $historico);

        self::where('descricao_bancaria', $baseHistorico)
            ->delete();
    }

}
