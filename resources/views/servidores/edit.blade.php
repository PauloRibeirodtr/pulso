@extends('adminlte::page')

@section('title', 'Editar servidor')

@section('content_header')
    <h1 class="m-0">Editar servidor</h1>
@stop

@section('content')
    <div class="container-fluid">
        <x-flash-message />
        <div class="card card-outline card-primary"><div class="card-header"><h2 class="card-title">Dados de {{ $servidor->name }}</h2></div><div class="card-body">
            <x-servidores.form :action="route('servidores.update', $servidor)" method="PUT" :$servidor />
        </div></div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h2 class="card-title">Permissões cadastradas</h2></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nível</th><th>Alcance</th><th>Função</th><th>Situação</th><th class="text-right">Ações</th></tr></thead>
                    <tbody>
                        @forelse ($servidor->permissoes as $permissao)
                            <tr>
                                <td>{{ ucfirst($permissao->nivel_escopo->value) }}</td>
                                <td>
                                    @if ($permissao->campus)
                                        {{ $permissao->campus->nome }}
                                    @elseif ($permissao->curso)
                                        {{ $permissao->curso->nome }}
                                    @else
                                        Todos os campi e cursos
                                    @endif
                                </td>
                                <td>{{ $permissao->papel }}</td>
                                <td><x-status-badge :active="$permissao->ativo" /></td>
                                <td class="text-right">
                                    <a href="{{ route('permissoes.edit', $permissao) }}" class="btn btn-sm btn-default mb-1">Editar</a>
                                    <form method="POST" action="{{ route('permissoes.update', $permissao) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="ativo" value="{{ $permissao->ativo ? '0' : '1' }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            {{ $permissao->ativo ? 'Desativar' : 'Reativar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma permissão cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-outline card-primary"><div class="card-header"><h2 class="card-title">Adicionar permissão</h2></div><div class="card-body">
            <form action="{{ route('servidores.permissoes.store', $servidor) }}" method="POST">
                @csrf
                <x-form-errors />
                <x-servidores.permissao-fields :$campi :$cursos />
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Adicionar permissão</button>
            </form>
        </div></div>
    </div>
@stop
