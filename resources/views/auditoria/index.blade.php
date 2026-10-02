@extends('adminlte::page')

@section('title', 'Auditoria')

@section('content_header')
    <div>
        <h1 class="mb-1">Auditoria</h1>
        <p class="text-muted mb-0">Operações administrativas relevantes e exportações realizadas no sistema.</p>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead><tr><th>Data e hora</th><th>Responsável</th><th>Ação</th><th>Registro</th></tr></thead>
                <tbody>
                    @forelse($registros as $registro)
                        <tr>
                            <td>{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $registro->usuario->name }}<br><small class="text-muted">{{ $registro->usuario->identificador_institucional }}</small></td>
                            <td>{{ str_replace(['criado', 'alterado', 'excluido', 'indicadores.exportados'], ['Criação', 'Alteração', 'Exclusão', 'Exportação de indicadores'], $registro->acao) }}</td>
                            <td>{{ $registro->entidade_tipo }} #{{ $registro->entidade_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Ainda não há operações registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $registros->links('pagination::bootstrap-4') }}</div>
    </div>
    <p class="text-muted small">Este registro não contém respostas, comentários, senhas ou detalhes de atendimentos.</p>
@stop
