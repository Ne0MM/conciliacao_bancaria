@extends('default.layout', ['title' => 'Conciliação Bancária'])

@section('content')
<div class="page-content">
    <div class="card">
        <div class="card-body p-4">
            <h5>Conciliação Bancária - Importação OFX</h5>
            <hr>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('financeiro.ofx.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="file" class="form-label">Selecione o Arquivo OFX</label>
                            <input type="file" class="form-control" id="file" name="file" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="banco" class="form-label">Conta Bancária</label>
                            <select class="form-select" id="banco" name="banco" required>
                                @foreach($bancos as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-start text-center">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-upload"></i> Enviar
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <div class="card">
                <div class="card-body">
                    <h5>Transações Encontradas</h5>
                    <div class="table-responsive">
                        <table class="table mb-0 table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição Banco</th>
                                    <th>Valor R$</th>
                                    <!--<th>Plano de Contas</th>-->
                                    <th></th>
                                    <th>Categoria</th>
                                    <!--
                                    <th>Valor Conciliado</th>
                                    -->
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transacoes as $transacao)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($transacao['data'])->format('d/m/Y') }}</td>
                                        <td class="text-break">{{ $transacao['historico'] }}</td>
                                        <td class="{{ $transacao['valor'] < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($transacao['valor'], 2, ',', '.') }}
                                        </td>
                                        @if ($transacao['categoria'] == null)
                                            <form action="{{route('financeiro.ofx.categorizar')}}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <td>
                                                    <button type="submit" class="btn btn-verde-claro--personalizado btn-sm">
                                                        <i class="bi bi-search fs-6"></i> Vincular Lançamentos
                                                    </button>
                                                </td>
                                                <input type="hidden" name="historico" value="{{ $transacao['historico'] }}">
                                                <td>
                                                    <input type="text" name="categoria" class="form-control form-control-sm" placeholder="Categoria" required>
                                                </td>
                                            </form>
                                        @else
                                            <form action="{{route('financeiro.ofx.descategorizar')}}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <td>
                                                    <button type="submit" class="btn btn-vermelho-claro--personalizado btn-sm">
                                                        <i class="bi bi-search fs-6"></i> Desvincular Lançamentos
                                                    </button>
                                                </td>
                                                <input type="hidden" name="historico" value="{{ $transacao['historico'] }}">
                                                <td>
                                                    {{ $transacao['categoria'] }}
                                                </td>
                                            </form>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Nenhuma transação encontrada</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .btn-verde-claro--personalizado {
        background-color: #00bc60 !important;
        color: #ebf0ec !important;
    }
    .btn-vermelho-claro--personalizado {
        background-color: #ff4d4d !important;
        color: #ebf0ec !important;
    }
    .btn-verde-claro--personalizado i {
        color: white !important;
    }
    .text-break {
        word-break: break-word;
        white-space: normal;
    }
    .table-responsive {
        overflow-x: auto;
    }
    input.form-control-sm {
        min-width: 120px;
    }
</style>
@endsection 