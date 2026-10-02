@extends('adminlte::page')
@section('title', 'Modelos de questionário')
@section('content_header')<div class="d-flex justify-content-between align-items-center"><h1>Modelos de questionário</h1><a class="btn btn-primary" href="{{ route('modelos-questionario.create') }}">Novo modelo</a></div>@stop
@section('content')
    <x-flash-message />
    <div class="card card-outline card-primary"><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Modelo</th><th>Bloco</th><th>Tipo</th><th>Perguntas</th><th>Estado</th><th>Ações</th></tr></thead><tbody>
    @forelse($modelos as $modelo)<tr><td>{{ $modelo->nome }}</td><td>{{ $modelo->curso?->sigla ?? $modelo->curso?->nome ?? 'Institucional' }}</td><td>{{ ucfirst($modelo->tipo->value) }}</td><td>{{ $modelo->itens_count }}</td><td>{{ $modelo->publicado_em ? 'Publicado' : 'Rascunho' }} @if(!$modelo->ativo)<span class="badge badge-secondary">Inativo</span>@endif</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('modelos-questionario.show', $modelo) }}">Abrir</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted">Nenhum modelo cadastrado.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $modelos->links('pagination::bootstrap-4') }}</div></div>
@stop
