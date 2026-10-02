@extends('adminlte::page')

@section('title', 'Turmas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Turmas</h1>

        @can('create', App\Models\Turma::class)
            <a href="{{ route('turmas.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nova turma
            </a>
        @endcan
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />

        <div class="card card-outline card-primary">
            <div class="card-header">
                <form method="GET" action="{{ route('turmas.index') }}" class="form-inline">
                    <label class="sr-only" for="busca">Buscar</label>
                    <input
                        id="busca"
                        type="search"
                        name="busca"
                        value="{{ request('busca') }}"
                        class="form-control mr-2"
                        placeholder="Nome ou código"
                    >
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Turma</th>
                            <th>Curso</th>
                            <th>Campus</th>
                            <th>Ingresso</th>
                            <th>Estudantes</th>
                            <th>Situação</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($turmas as $turma)
                            <tr>
                                <td>
                                    {{ $turma->nome_amigavel }}
                                    @if ($turma->codigo_academico)
                                        <small class="text-muted">({{ $turma->codigo_academico }})</small>
                                    @endif
                                </td>
                                <td>{{ $turma->curso->nome }}</td>
                                <td>{{ $turma->curso->campus->nome }}</td>
                                <td>
                                    {{ $turma->ano_ingresso ?? '—' }}
                                    @if ($turma->periodo_ingresso)
                                        / {{ $turma->periodo_ingresso }}
                                    @endif
                                </td>
                                <td>{{ $turma->estudantes_count }}</td>
                                <td><x-status-badge :active="$turma->ativo" /></td>
                                <td class="text-right">
                                    @can('update', $turma)
                                        <a href="{{ route('turmas.edit', $turma) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit mr-1"></i> Editar
                                        </a>
                                    @else
                                        <span class="text-muted">Somente consulta</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nenhuma turma encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($turmas->hasPages())
                <div class="card-footer">
                    {{ $turmas->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@stop
