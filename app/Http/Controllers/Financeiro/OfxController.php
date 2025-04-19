<?php

namespace App\Http\Controllers\Financeiro;


use App\Http\Controllers\Controller;
use App\Services\OfxService;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Cache;
use App\Models\ContaBancaria;
use App\Models\Lancamento;
use App\Models\Banco;
use App\Models\Deparas;
use Barryvdh\Debugbar\Facades\Debugbar;

class OfxController extends Controller
{
    protected $ofxService;

    public function __construct(OfxService $ofxService)
    {
        $this->ofxService = $ofxService;
    }

    public function index()
    {
        $transacoes = session('transacoes', []);
        \Debugbar::info("[DEBUG Index] transacoes: " . json_encode($transacoes));

        // Pega todas as transações do banco de dados
        $transacoes = Lancamento::orderBy('data', 'desc')->get()->map(function($lancamento) {
            return [
                'data' => $lancamento->data,
                'historico' => $lancamento->historico,
                'valor' => $lancamento->valor,
                'numero_documento' => $lancamento->numero_documento,
                'categoria' => $lancamento->categoria,
                'banco' => $lancamento->banco,
                'conta' => $lancamento->conta,
            ];
        })->toArray();

        $bancos = Banco::getAllBancos(); // Pega todos os bancos do banco de dados
        \Debugbar::info("[DEBUG Index] Bancos: " . json_encode($bancos));

        // Adiciona uma opção padrão para o select
        $bancos = ['' => 'Selecione uma conta bancária'] + $bancos;

        \Debugbar::info("[DEBUG Index] Contas bancárias enviadas para o formulário:", $bancos);

        return view('financeiro.ofx.index', compact('transacoes', 'bancos'));
    }


    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:ofx,txt,xml',
            'banco' => 'required|string',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        \Debugbar::info('Original file name: ' . $originalName);

        // Gera um nome único para o arquivo
        $timestamp = time();
        $filename = $timestamp . '_' . $originalName;
    
        // Move o arquivo para o diretório de uploads
        $path = $file->move(public_path('uploads'), $filename);
        $fileAbsolutePath = $path->getPathname();
        
        // Verifica se o arquivo foi movido corretamente
        if (!file_exists($fileAbsolutePath)) {
            return redirect()->route('financeiro.ofx.index')
                ->with('error', 'Não foi possível acessar o arquivo após o upload.');
        }

        $banco = Banco::find($request->banco); // Pega o banco selecionado
        $bancoNome = strtoupper($banco->nome); // Transforma o nome do banco em maiúsculas

        // Usa "contains" via stripos para identificar o banco
        switch (true) {
            case stripos($bancoNome, 'BANCO DO BRASIL') !== false:
                $dados = $this->ofxService->tratarBancoBB($fileAbsolutePath);
                break;
            case stripos($bancoNome, 'BRADESCO') !== false:
                $dados = $this->ofxService->tratarBancoBRADESCO($fileAbsolutePath);
                break;
            case stripos($bancoNome, 'SICOOB') !== false:
                $dados = $this->ofxService->tratarBancoSICOOB($fileAbsolutePath);
                break;
            case stripos($bancoNome, 'C6') !== false:
                $dados = $this->ofxService->tratarBancoC6($fileAbsolutePath);
                break;
            default:
                \Debugbar::error("[DEBUG Upload] Banco {$bancoNome} não suportado.");
                return redirect()->route('financeiro.ofx.index')->with('error', "Banco {$bancoNome} não suportado.");
        }

        \Debugbar::info("[DEBUG Upload] Dados processados:", $dados);

        // Verifica se foram encontradas transações
        if (empty($dados['transacoes'])) {
            return redirect()->route('financeiro.ofx.index')
                ->with('error', 'Nenhuma transação encontrada no arquivo.');
        }

        // Loop para cada transação
        foreach($dados['transacoes'] as $transacao){

            $bancoConta = $request->banco; // Pega o banco selecionado
            $categoria = null; // Inicializa a categoria como nula

            // Checar se a o lancamento existe
            $existingTransaction = Lancamento::checkForDuplicates($transacao);

            if($existingTransaction) {
                \Debugbar::info("[DEBUG Upload] Transação já existe: " . $transacao['historico']);
                continue; // Pula para a próxima iteração
            }

            // Verifica se existe uma categoria associada ao histórico
            $existingDeparas = Deparas::checkForExistingDeparas($transacao['historico']);

            if($existingDeparas) {
                $categoria = $existingDeparas->categoria; // Se existir, pega a categoria
            }

            // Cria o lançamento
            Lancamento::createLancamento($transacao + [
                'banco' => $bancoConta,
                'conta' => $bancoConta,
                'categoria' => $categoria,
            ]);
        }

        return redirect()->route('financeiro.ofx.index')
            ->with('success', 'Arquivo processado com sucesso!')
            ->with('transacoes', $dados['transacoes']);
    }

    public function categorizar(Request $request)
    {

        $request->validate([
            'historico' => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
        ]);

        // Cria o depara
        Deparas::createDeparasWithNormalizedHistorico($request->historico, $request->categoria);

        // Pega todas as transações com o mesmo histórico
        $transacoesWithSimilarHistorico = Lancamento::getLancamentosWithSimiliarHistorico($request->historico);

        // Loop para cada transação
        foreach ($transacoesWithSimilarHistorico as $transacao) {
            // Atualiza a categoria da transação
            $transacao->update([
                'categoria' => $request->categoria,
            ]);
        }

        return redirect()->route('financeiro.ofx.index')
            ->with('success', 'Transações categorizadas com sucesso!');

    }

    public function descategorizar(Request $request)
    {
        $request->validate([
            'historico' => 'required|string|max:255',
        ]);

        // Deleta a categoria de lancamentos com o mesmo histórico
        Lancamento::removeCategoria($request->historico);

        // Remove o depara
        Deparas::removeDepara($request->historico);

        return redirect()->route('financeiro.ofx.index')
            ->with('success', 'Transações descategorizadas com sucesso!');
    }

} 