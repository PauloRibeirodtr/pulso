@extends('adminlte::page')

@section('title', 'Solicitações de conversa')

@section('content_header')
    <div><h1 class="mb-1">Solicitações de conversa</h1><p class="text-muted mb-0">Pedidos feitos pelos estudantes, limitados aos cursos sob sua responsabilidade.</p></div>
@stop

@section('content')
    <x-flash-message />
    <x-form-errors />
    <div class="card card-outline card-primary"><div class="card-header"><form method="GET" action="{{ route('solicitacoes.index') }}" class="form-inline"><label for="status" class="mr-2">Situação</label><select id="status" name="status" class="form-control mr-2"><option value="pendente" @selected($status === 'pendente')>Pendentes</option><option value="atendida" @selected($status === 'atendida')>Atendidas</option><option value="todas" @selected($status === 'todas')>Todas</option></select><button class="btn btn-outline-primary">Filtrar</button></form></div>
        <div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Estudante</th><th>Curso / turma</th><th>Solicitada em</th><th>Situação</th><th>Atendimento</th><th>Ação</th></tr></thead><tbody>
            @forelse($solicitacoes as $solicitacao)
                @php($participacao = $solicitacao->participacao)
                <tr><td>{{ $participacao->estudante->usuario->name }}<br><small class="text-muted">Matrícula {{ $participacao->estudante->usuario->identificador_institucional }}</small></td>
                    <td>{{ $participacao->aplicacao->turma->curso->sigla ?: $participacao->aplicacao->turma->curso->nome }} / {{ $participacao->aplicacao->turma->nome_amigavel }}<br><small class="text-muted">{{ $participacao->aplicacao->nome }}</small></td>
                    <td>{{ $solicitacao->solicitado_em->format('d/m/Y H:i') }}</td>
                    <td><span class="badge {{ $solicitacao->status === App\StatusSolicitacaoAcompanhamento::Pendente ? 'badge-warning' : 'badge-success' }}">{{ $solicitacao->status === App\StatusSolicitacaoAcompanhamento::Pendente ? 'Pendente' : 'Atendida' }}</span></td>
                    <td>@if($solicitacao->atendido_em){{ $solicitacao->atendido_em->format('d/m/Y H:i') }}<br><small class="text-muted">{{ $solicitacao->responsavel?->name }}</small>@else—@endif</td>
                    <td>@can('attend', $solicitacao)<form method="POST" action="{{ route('solicitacoes.attend', $solicitacao) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" onclick="return confirm('Confirmar que o atendimento foi realizado?')">Marcar atendida</button></form>@else—@endcan</td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma solicitação encontrada.</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $solicitacoes->links('pagination::bootstrap-4') }}</div>
    </div>
    <p class="text-muted small">O Pulso IFMS registra apenas a solicitação e a confirmação de atendimento. Não registre aqui o conteúdo da conversa ou providências tomadas.</p>
@stop
