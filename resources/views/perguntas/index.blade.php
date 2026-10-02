@extends('adminlte::page')
@section('title', 'Perguntas')
@section('content_header')<div class="d-flex justify-content-between align-items-center"><h1>Perguntas</h1><a class="btn btn-primary" href="{{ route('perguntas.create') }}">Nova pergunta</a></div>@stop
@section('content')
    <x-flash-message />
    <div class="card card-outline card-primary"><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Pergunta</th><th>Dimensão</th><th>Bloco</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
        @forelse($perguntas as $pergunta)<tr><td>{{ $pergunta->texto }} @if($pergunta->sensivel)<span class="badge badge-warning">Sensível</span>@endif</td><td>{{ $pergunta->dimensao->nome }}</td><td>{{ $pergunta->cursoOrigem?->sigla ?? $pergunta->cursoOrigem?->nome ?? 'Institucional' }}</td><td><x-status-badge :active="$pergunta->ativo" /></td><td>@can('update', $pergunta)<a class="btn btn-sm btn-outline-primary" href="{{ route('perguntas.edit', $pergunta) }}">Editar</a>@else<span class="text-muted">Congelada</span>@endcan</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted">Nenhuma pergunta cadastrada.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $perguntas->links('pagination::bootstrap-4') }}</div></div>
@stop
