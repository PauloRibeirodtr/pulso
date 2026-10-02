@extends('adminlte::page')

@section('title', 'Servidores')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Servidores e permissões</h1>
        <a href="{{ route('servidores.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Novo servidor</a>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />
        <div class="card card-outline card-primary">
            <div class="card-header">
                <form method="GET" action="{{ route('servidores.index') }}" class="form-inline">
                    <label for="busca" class="sr-only">Buscar por nome, SIAPE ou e-mail</label>
                    <input id="busca" name="busca" type="search" value="{{ request('busca') }}" class="form-control mr-2" placeholder="Nome, SIAPE ou e-mail">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Servidor</th><th>SIAPE</th><th>E-mail</th><th>Permissões ativas</th><th>Situação</th><th class="text-right">Ações</th></tr></thead>
                    <tbody>
                        @forelse ($servidores as $servidor)
                            <tr>
                                <td>{{ $servidor->name }}</td>
                                <td>{{ $servidor->identificador_institucional }}</td>
                                <td>{{ $servidor->email ?? '—' }}</td>
                                <td>{{ $servidor->permissoes_ativas_count }}</td>
                                <td><x-status-badge :active="$servidor->ativo" /></td>
                                <td class="text-right">
                                    @can('update', $servidor)
                                        <a href="{{ route('servidores.edit', $servidor) }}" class="btn btn-sm btn-outline-primary">Editar e gerenciar acesso</a>
                                    @else
                                        <span class="text-muted">Conta própria</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum servidor encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($servidores->hasPages())
                <div class="card-footer">{{ $servidores->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
@stop
