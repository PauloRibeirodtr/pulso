@extends('adminlte::page')

@section('title', 'Estudantes')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Estudantes</h1>
        @can('create', App\Models\Estudante::class)
            <div>
                <a href="{{ route('estudantes.import.form') }}" class="btn btn-outline-primary mr-2"><i class="fas fa-file-import mr-1"></i> Importar CSV</a>
                <a href="{{ route('estudantes.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Novo estudante</a>
            </div>
        @endcan
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />
        <div class="card card-outline card-primary">
            <div class="card-header">
                <form method="GET" action="{{ route('estudantes.index') }}" class="form-inline">
                    <label for="busca" class="sr-only">Buscar por nome, matrícula ou turma</label>
                    <input id="busca" name="busca" type="search" value="{{ request('busca') }}" class="form-control mr-2" placeholder="Nome, matrícula ou turma">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Estudante</th><th>Matrícula</th><th>Turma</th><th>Curso</th><th>Campus</th><th>Situação</th><th class="text-right">Ações</th></tr></thead>
                    <tbody>
                        @forelse ($estudantes as $estudante)
                            <tr>
                                <td>{{ $estudante->usuario->name }}</td>
                                <td>{{ $estudante->usuario->identificador_institucional }}</td>
                                <td>{{ $estudante->turma->nome_amigavel }}</td>
                                <td>{{ $estudante->turma->curso->sigla }}</td>
                                <td>{{ $estudante->turma->curso->campus->sigla }}</td>
                                <td><x-status-badge :active="$estudante->situacao_academica === 'ativo'" /></td>
                                <td class="text-right">
                                    <a href="{{ route('estudantes.edit', $estudante) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    @can('resetPassword', $estudante)
                                        <form method="POST" action="{{ route('estudantes.password.reset', $estudante) }}" class="d-inline">
                                            @csrf @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-default" onclick="return confirm('Redefinir a senha deste estudante para o nome amigável da turma atual?')">Redefinir senha</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum estudante encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($estudantes->hasPages())
                <div class="card-footer">{{ $estudantes->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
@stop
