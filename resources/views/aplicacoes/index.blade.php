@extends('adminlte::page')
@section('title', 'Aplicações de pesquisa')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Aplicações de pesquisa</h1>
        @can('create', App\Models\AplicacaoPesquisa::class)<a href="{{ route('aplicacoes.create') }}" class="btn btn-primary">Nova aplicação</a>@endcan
    </div>
@stop
@section('content')
    <x-flash-message />
    <div class="card card-outline card-primary"><div class="card-body table-responsive p-0">
        <table class="table table-hover"><thead><tr><th>Aplicação</th><th>Curso / turma</th><th>Tipo</th><th>Período</th><th>Situação</th><th>Participação</th><th>Ações</th></tr></thead><tbody>
        @forelse($aplicacoes as $aplicacao)
            <tr><td>{{ $aplicacao->nome }}</td><td>{{ $aplicacao->turma->curso->sigla ?: $aplicacao->turma->curso->nome }} / {{ $aplicacao->turma->nome_amigavel }}</td><td>{{ ucfirst($aplicacao->tipo_aplicacao->value) }}</td>
                <td>{{ $aplicacao->data_abertura?->format('d/m/Y H:i') ?? 'A definir' }} — {{ $aplicacao->data_encerramento?->format('d/m/Y H:i') ?? 'Sem prazo' }}</td>
                <td>{{ ucfirst($aplicacao->status->value) }} @if($aplicacao->status === App\StatusAplicacao::Aberta && $aplicacao->data_encerramento?->isPast())<span class="badge badge-warning">Prazo expirado</span>@endif</td>
                <td>{{ $aplicacao->finalizadas_count }}/{{ $aplicacao->participacoes_count }}</td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('aplicacoes.show', $aplicacao) }}">Abrir</a></td></tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-4">Nenhuma aplicação cadastrada.</td></tr>@endforelse
        </tbody></table>
    </div><div class="card-footer">{{ $aplicacoes->links('pagination::bootstrap-4') }}</div></div>
@stop
