<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use OfxParser\Parser;

class OfxService
{
    /**
     * Verifica se o arquivo OFX está em formato SGML (antigo) ou XML.
     */
    public  function isSGML($filePath)
    {
        $firstLines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($firstLines as $line) {
            if (stripos($line, "OFXHEADER:") !== false || stripos($line, "DATA:OFXSGML") !== false) {
                return true; // Formato SGML
            }
            if (stripos($line, "<?xml") !== false) {
                return false; // Formato XML válido
            }
        }
        return true; // Assume SGML se não encontrar XML
    }

    /**
     * Processa o arquivo OFX especificamente para Banco do Brasil (BB).
     */
    public function tratarBancoBB($filePath)
    {
        // Lê o conteúdo do arquivo OFX
        $content = file_get_contents($filePath);
        $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'ISO-8859-1, UTF-8, ASCII', true));

        // Expressão regular para capturar o número do banco e da conta
        preg_match('/<BANKID>\s*(\d+)\s*<ACCTID>\s*([\d-]+)/', $content, $bancoContaMatch);
        $banco = isset($bancoContaMatch[1]) ? trim($bancoContaMatch[1]) : 'N/A';
        $conta = isset($bancoContaMatch[2]) ? trim($bancoContaMatch[2]) : 'N/A';

        // Expressão regular para capturar todas as transações dentro de <STMTTRN>...</STMTTRN>
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $stmtMatches, PREG_SET_ORDER);

        $transacoes = [];

        foreach ($stmtMatches as $stmtMatch) {
            $stmt = $stmtMatch[1];
            
            // Extrai a data
            preg_match('/<DTPOSTED>\s*([\d]+)/', $stmt, $dataMatch);
            $data = $this->formatarDataOfx(trim($dataMatch[1]));
            
            // Extrai o valor
            preg_match('/<TRNAMT>\s*([-+]?\d+(\.\d+)?)/', $stmt, $valorMatch);
            $valor = number_format((float) trim($valorMatch[1]), 2, '.', '');
            
            // Extrai o número do documento
            $documento = '';
            if (preg_match('/<CHECKNUM>(.*?)<\/CHECKNUM>/', $stmt, $docMatch)) {
                $documento = trim($docMatch[1]);
            }
            
            // Extrai o histórico
            $historico = 'Sem descrição';
            if (preg_match('/<MEMO>(.*?)<\/MEMO>/', $stmt, $memoMatch)) {
                $historico = trim($memoMatch[1]);
            }
            
            // Concerta o encodingo do histórico
            $historico = str_replace('', '', $historico);

            $historico = utf8_decode($historico) ?? 'N/A';

            // Adiciona a transação ao array
            $transacoes[] = [
                'data' => $data,
                'valor' => $valor,
                'historico' => $historico,
                'numero_documento' => $documento
            ];
        }


        return [
            "banco" => $banco,
            "conta" => $conta,
            "transacoes" => $transacoes
        ];
    }


    /**
     * Converte a data do formato AAAAMMDD para YYYY-MM-DD.
     */
    public  function formatarDataOfx($data)
    {
        return substr($data, 0, 4) . '-' . substr($data, 4, 2) . '-' . substr($data, 6, 2);
    }


    /**
     * Tratamento personalizado para o Bradesco.
     */
    public  function tratarBancoBRADESCO($contaInfo)
    {
        return [
            "agencia" => $contaInfo->routingNumber ?? 'N/A',
            "conta" => $contaInfo->accountId ?? 'N/A',
            "transacoes" => $this->processarTransacoes($contaInfo)
        ];
    }

    /**
     * Tratamento personalizado para o Sicoob.
     */
    public  function tratarBancoSICOOB($contaInfo)
    {
        return [
            "agencia" => $contaInfo->branchId ?? 'N/A',
            "conta" => $contaInfo->accountId ?? 'N/A',
            "transacoes" => $this->processarTransacoes($contaInfo)
        ];
    }

    /**
     * Tratamento personalizado para o C6 Bank.
     */
    public  function tratarBancoC6($contaInfo)
    {
        return [
            "agencia" => $contaInfo->branchId ?? 'N/A',
            "conta" => $contaInfo->accountId ?? 'N/A',
            "transacoes" => $this->processarTransacoes($contaInfo)
        ];
    }


    /**
     * Mapeia a conta bancária conforme o banco.
     */
    public  function mapearContaBancaria($contaInfo, $banco)
    {
        switch ($banco) {
            case 'SICOOB':
            case 'C6':
            case 'BB':
                return [$contaInfo->branchId ?? 'N/A', $contaInfo->accountId ?? 'N/A'];
            case 'BRADESCO':
                return [$contaInfo->routingNumber ?? 'N/A', $contaInfo->accountId ?? 'N/A'];
            default:
                throw new \Exception("Banco {$banco} não suportado.");
        }
    }

    /**
     * Processa as transações do extrato bancário.
     */
    public  function processarTransacoes($contaInfo)
    {
        $transacoes = [];

        if (!isset($contaInfo->statement->transactions) || empty($contaInfo->statement->transactions)) {
            throw new \Exception("Nenhuma transação encontrada no arquivo OFX.");
        }

        foreach ($contaInfo->statement->transactions as $transacao) {
            $transacoes[] = [
                'data' => $transacao->date->format("Y-m-d"),
                'valor' => number_format($transacao->amount, 2, '.', ''),
                'historico' => $transacao->memo ?? "Sem descrição",
                'numero_documento' => $transacao->checkNumber ?? ''
            ];
        }

        return $transacoes;
    }

} 