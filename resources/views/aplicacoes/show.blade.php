@extends('adminlte::page')
@section('title', $aplicacao->nome)
@section('content_header')<div class="d-flex justify-content-between align-items-center"><h1>{{ $aplicacao->nome }}</h1><a href="{{ route('aplicacoes.index') }}" class="btn btn-outline-secondary">Voltar</a></div>@stop
@section('content')
    <x-flash-message /><x-form-errors />
    <div class="card card-outline card-primary"><div class="card-body">
        <p><strong>Turma:</strong> {{ $aplicacao->turma->nome_amigavel }} · <strong>Curso:</strong> {{ $aplicacao->turma->curso->nome }} · <strong>Campus:</strong> {{ $aplicacao->turma->curso->campus->sigla }}</p>
        <p><strong>Tipo:</strong> {{ ucfirst($aplicacao->tipo_aplicacao->value) }} · <strong>Situação:</strong> {{ ucfirst($aplicacao->status->value) }} · <strong>Participação:</strong> {{ $aplicacao->finalizadas_count }}/{{ $aplicacao->participacoes_count }} finalizadas</p>
        <p><strong>Abertura:</strong> {{ $aplicacao->data_abertura?->format('d/m/Y H:i') ?? 'A definir' }} · <strong>Encerramento:</strong> {{ $aplicacao->data_encerramento?->format('d/m/Y H:i') ?? 'Sem prazo' }}</p>
        @if($aplicacao->status === App\StatusAplicacao::Aberta && $aplicacao->data_encerramento?->isPast())<p class="alert alert-warning">O prazo terminou. Encerre a aplicação ou prorrogue o prazo.</p>@endif
        @can('update', $aplicacao)<a class="btn btn-outline-primary" href="{{ route('aplicacoes.edit', $aplicacao) }}">Editar planejamento</a><form method="POST" action="{{ route('aplicacoes.open', $aplicacao) }}" class="d-inline">@csrf <button class="btn btn-success" onclick="return confirm('Abrir esta aplicação para a turma?')">Abrir para respostas</button></form>@endcan
        @can('close', $aplicacao)<form method="POST" action="{{ route('aplicacoes.close', $aplicacao) }}" class="d-inline">@csrf <button class="btn btn-outline-danger" onclick="return confirm('Encerrar esta aplicação?')">Encerrar aplicação</button></form>@endcan
        @can('extend', $aplicacao)<form method="POST" action="{{ route('aplicacoes.extend', $aplicacao) }}" class="form-inline mt-3">@csrf @method('PATCH')<label for="novo_prazo" class="mr-2">Prorrogar até</label><input id="novo_prazo" type="datetime-local" name="data_encerramento" class="form-control mr-2" required><button class="btn btn-outline-primary">Prorrogar</button></form>@endcan
    </div></div>
    <div class="card"><div class="card-header"><strong>Composição congelada</strong></div><div class="card-body">
        @foreach($aplicacao->modelosQuestionario as $modelo)<p class="mb-1"><strong>Modelo:</strong> {{ $modelo->nome }}</p>@endforeach
        @forelse($aplicacao->perguntas as $pergunta)<div class="border-top py-3"><strong>{{ $pergunta->ordem }}.</strong> {{ $pergunta->texto_snapshot }} <span class="badge badge-info">{{ ucfirst($pergunta->origem->value) }}</span> @if($pergunta->obrigatoria)<span class="badge badge-secondary">Obrigatória</span>@endif<br><small class="text-muted">{{ $pergunta->pergunta->dimensao->nome }} · Escala {{ $pergunta->valor_minimo_snapshot }}–{{ $pergunta->valor_maximo_snapshot }}</small></div>
        @empty<p class="text-muted">Nenhuma pergunta selecionada.</p>@endforelse
    </div></div>
    <div class="card"><div class="card-header"><strong>Participação da turma</strong></div><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Estudante</th><th>Matrícula</th><th>Situação</th><th>Finalização</th></tr></thead><tbody>
        @forelse($participacoes as $participacao)<tr><td>{{ $participacao->estudante->usuario->name }}</td><td>{{ $participacao->estudante->usuario->identificador_institucional }}</td><td>{{ str_replace('_', ' ', ucfirst($participacao->status->value)) }}</td><td>{{ $participacao->finalizado_em?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted">Nenhuma participação registrada. Os estudantes ativos serão incluídos quando a aplicação for aberta.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $participacoes->links('pagination::bootstrap-4') }}</div></div>
@stop
