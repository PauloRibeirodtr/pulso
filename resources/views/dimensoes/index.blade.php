@extends('adminlte::page')
@section('title', 'Dimensões')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center"><h1>Dimensões</h1>
        @can('create', App\Models\Dimensao::class)<a class="btn btn-primary" href="{{ route('dimensoes.create') }}">Nova dimensão</a>@endcan
    </div>
@stop
@section('content')
    <x-flash-message />
    <div class="card card-outline card-primary"><div class="card-body table-responsive p-0"><table class="table table-hover">
        <thead><tr><th>Nome</th><th>Perguntas</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
        @forelse($dimensoes as $dimensao)
            <tr><td>{{ $dimensao->nome }}</td><td>{{ $dimensao->perguntas_count }}</td><td><x-status-badge :active="$dimensao->ativo" /></td><td>
                @can('update', $dimensao)<a class="btn btn-sm btn-outline-primary" href="{{ route('dimensoes.edit', $dimensao) }}">Editar</a>@endcan
            </td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted">Nenhuma dimensão cadastrada.</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $dimensoes->links('pagination::bootstrap-4') }}</div></div>
@stop
