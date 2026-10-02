@extends('adminlte::page')

@section('title', 'Indicadores — '.$aplicacao->nome)

@section('content_header')
    <div class="d-flex justify-content-between align-items-start flex-wrap"><div><h1 class="mb-1">{{ $aplicacao->nome }}</h1><p class="text-muted mb-0">{{ $aplicacao->turma->curso->campus->sigla }} · {{ $aplicacao->turma->curso->sigla ?: $aplicacao->turma->curso->nome }} · {{ $aplicacao->turma->nome_amigavel }}</p></div><div class="d-flex flex-wrap"><a href="{{ route('indicadores.export', $aplicacao) }}" class="btn btn-primary mr-2 mb-2">Exportar CSV agregado</a><a href="{{ route('indicadores.index') }}" class="btn btn-outline-secondary mb-2">Voltar aos indicadores</a></div></div>
@stop

@section('content')
    <div class="card card-outline card-primary"><div class="card-body"><p class="mb-1"><strong>Tipo:</strong> {{ ucfirst($aplicacao->tipo_aplicacao->value) }} · <strong>Situação:</strong> {{ ucfirst($aplicacao->status->value) }}</p><p class="mb-0"><strong>Participações finalizadas:</strong> {{ $aplicacao->finalizadas_count }} de {{ $aplicacao->participacoes_count }} · <strong>Abertura:</strong> {{ $aplicacao->data_abertura?->format('d/m/Y H:i') ?? '—' }}</p></div></div>
    <p class="text-muted small">Só são consideradas respostas de pesquisas finalizadas. Médias com menos de {{ App\Services\IndicadoresPesquisa::MIN_PARTICIPANTES }} estudantes distintos são ocultadas; nenhum resultado individual é exibido neste painel.</p>

    <div class="card"><div class="card-header"><h2 class="card-title">Médias por dimensão e bloco</h2></div><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Bloco</th><th>Dimensão</th><th>Estudantes</th><th>Respostas</th><th>Média (1 a 5)</th></tr></thead><tbody>
        @forelse($dimensoes as $dimensao)<tr><td>{{ ucfirst($dimensao->origem) }}</td><td>{{ $dimensao->dimensao }}</td><td>{{ $dimensao->estudantes }}</td><td>{{ $dimensao->respostas }}</td><td>{{ $indicadores->mediaPublicavel($dimensao) !== null ? number_format($indicadores->mediaPublicavel($dimensao), 2, ',', '.') : 'Restrita' }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted">Ainda não há respostas finalizadas nesta aplicação.</td></tr>@endforelse
    </tbody></table></div></div>

    <div class="card"><div class="card-header"><h2 class="card-title">Histórico da mesma turma e tipo de pesquisa</h2></div><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Aplicação</th><th>Abertura</th><th>Finalizadas</th><th>Média institucional</th></tr></thead><tbody>
        @forelse($historico as $item)
            @php($resumo = $mediasHistoricas->get($item->id))
            <tr @if($item->id === $aplicacao->id) class="table-active" @endif><td>{{ $item->nome }}</td><td>{{ $item->data_abertura?->format('d/m/Y') ?? '—' }}</td><td>{{ $item->finalizadas_count }}/{{ $item->participacoes_count }}</td><td>{{ $indicadores->mediaPublicavel($resumo) !== null ? number_format($indicadores->mediaPublicavel($resumo), 2, ',', '.') : 'Restrita' }}</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted">Não há aplicações comparáveis desta turma.</td></tr>@endforelse
    </tbody></table></div></div>

    <p class="text-muted small">O histórico usa somente o bloco institucional. Perguntas e versões diferentes podem alterar a comparabilidade das médias; o bloco temporário é mostrado apenas na aplicação em que foi usado.</p>
@stop
