<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lancamento extends Model
{
    protected $table = 'lancamentos';

    protected $fillable = [
        'data',
        'valor',
        'historico',
        'numero_documento',
        'categoria',
        'banco',
        'conta'
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
    ];

    // Checa se existe um lançamento com os mesmos dados
    static public function checkForDuplicates(array $transacao)
    {
        return self::where('data', $transacao['data'])
            ->where('historico', $transacao['historico'])
            ->where('valor', $transacao['valor'])
            ->where('numero_documento', $transacao['numero_documento'])
            ->first();
    }

    // Cria um novo lançamento
    static public function createLancamento(array $transacao)
    {
        return self::create([
            'data' => $transacao['data'],
            'valor' => $transacao['valor'],
            'historico' => $transacao['historico'],
            'numero_documento' => $transacao['numero_documento'],
            'categoria' => $transacao['categoria'],
            'banco' => $transacao['banco'],
            'conta' => $transacao['conta'],
        ]);
    }

    // Retorna os lançamentos que possuem um histórico semelhante
    static public function getLancamentosWithSimiliarHistorico($historico)
    {

        $datePattern = '/\d{2}\/\d{2}\s\d{2}:\d{2}/';
        $baseHistorico = preg_replace($datePattern, '', $historico);

        return self::get()->filter(function($lancamento) use ($baseHistorico, $datePattern) {
            $lancamentoBaseDesc = preg_replace($datePattern, '', $lancamento->historico);
            return stripos($lancamentoBaseDesc, $baseHistorico) !== false;
        });
    }

    static public function removeCategoria($historico)
    {
        $datePattern = '/\d{2}\/\d{2}\s\d{2}:\d{2}/';
        $baseHistorico = preg_replace($datePattern, '', $historico);

        $lancamentos = self::get()->filter(function($lancamento) use ($baseHistorico, $datePattern) {
            $lancamentoBaseDesc = preg_replace($datePattern, '', $lancamento->historico);
            return stripos($lancamentoBaseDesc, $baseHistorico) !== false;
        });

        foreach ($lancamentos as $lancamento) {
            $lancamento->update([
                'categoria' => null,
            ]);
        }
    }

}
