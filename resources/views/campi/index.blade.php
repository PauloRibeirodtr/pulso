@extends('adminlte::page')

@section('title', 'Campi')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Campi</h1>

        @can('create', App\Models\Campus::class)
            <a href="{{ route('campi.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Novo campus
            </a>
        @endcan
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />

        <div class="card card-outline card-primary">
            <div class="card-header">
                <form method="GET" action="{{ route('campi.index') }}" class="form-inline">
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
                            <th>Nome</th>
                            <th>Sigla</th>
                            <th>Cursos</th>
                            <th>Situação</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campi as $campus)
                            <tr>
                                <td>{{ $campus->nome }}</td>
                                <td>{{ $campus->sigla ?: '—' }}</td>
                                <td>{{ $campus->cursos_count }}</td>
                                <td><x-status-badge :active="$campus->ativo" /></td>
                                <td class="text-right">
                                    @can('update', $campus)
                                        <a href="{{ route('campi.edit', $campus) }}" class="btn btn-sm btn-outline-primary">
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
                                    Nenhum campus encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campi->hasPages())
                <div class="card-footer">
                    {{ $campi->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@stop
