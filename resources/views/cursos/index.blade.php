@extends('adminlte::page')

@section('title', 'Cursos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Cursos</h1>

        @can('create', App\Models\Curso::class)
            <a href="{{ route('cursos.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Novo curso
            </a>
        @endcan
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />

        <div class="card card-outline card-primary">
            <div class="card-header">
                <form method="GET" action="{{ route('cursos.index') }}" class="form-inline">
                    <label class="sr-only" for="busca">Buscar</label>
                    <input
                        id="busca"
                        type="search"
                        name="busca"
                        value="{{ request('busca') }}"
                        class="form-control mr-2"
                        placeholder="Nome ou sigla"
                    >
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Campus</th>
                            <th>Turmas</th>
                            <th>Situação</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cursos as $curso)
                            <tr>
                                <td>
                                    {{ $curso->nome }}
                                    @if ($curso->sigla)
                                        <small class="text-muted">({{ $curso->sigla }})</small>
                                    @endif
                                </td>
                                <td>{{ $curso->campus->nome }}</td>
                                <td>{{ $curso->turmas_count }}</td>
                                <td><x-status-badge :active="$curso->ativo" /></td>
                                <td class="text-right">
                                    @can('update', $curso)
                                        <a href="{{ route('cursos.edit', $curso) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </a>
                                    @else
                                        <span class="text-muted">Somente consulta</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Nenhum curso encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cursos->hasPages())
                <div class="card-footer">
                    {{ $cursos->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@stop
