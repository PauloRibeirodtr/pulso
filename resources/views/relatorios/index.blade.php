@extends('adminlte::page')

@section('title', 'Relatórios')

@section('content_header')
    <h1>Relatórios</h1>
    <p class="text-muted mb-0">Selecione as aplicações e explore participação, percepções e solicitações de conversa.</p>
@stop

@section('content')
    <x-form-errors />
    <div class="row report-no-print">
        @foreach($titulos as $chave => $titulo)
            <div class="col-md-6 col-xl-3 mb-3"><a class="card h-100 mb-0 text-body {{ $relatorio === $chave ? 'card-outline card-primary' : '' }}" href="{{ route('relatorios.index', ['relatorio' => $chave]) }}"><div class="card-body"><h2 class="h6 font-weight-bold">{{ $titulo }}</h2><p class="small text-muted mb-0">{{ match($chave) { 'resultado' => 'Médias e distribuição das notas por pergunta.', 'historico' => 'Evolução das dimensões da mesma turma e tipo.', 'participacao' => 'Adesão e situação das participações.', 'solicitacoes' => 'Pendências, atendimentos e tempos de espera.' } }}</p></div></a></div>
        @endforeach
    </div>
    <form method="GET" action="{{ route('relatorios.index') }}" class="card report-no-print" id="report-form">
        <details class="report-disclosure">
        <summary class="card-header"><span class="font-weight-bold">Personalizar relatório</span><span class="report-toggle-hint small text-muted"><span class="report-when-closed">Exibir filtros</span><span class="report-when-open">Recolher filtros</span></span></summary>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6"><label for="relatorio">Relatório</label><select name="relatorio" id="relatorio" class="form-control">@foreach($titulos as $chave => $titulo)<option value="{{ $chave }}" @selected($relatorio === $chave)>{{ $titulo }}</option>@endforeach</select></div>
                <div class="form-group col-md-6"><label for="modo">Apresentação</label><select name="modo" id="modo" class="form-control">@foreach(['comparativo' => 'Comparativo — aplicações separadas', 'consolidado' => 'Consolidado — respostas agrupadas', 'individual' => 'Individual — uma aplicação'] as $chave => $titulo)<option value="{{ $chave }}" @selected(($filtros['modo'] ?? 'comparativo') === $chave)>{{ $titulo }}</option>@endforeach</select></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4"><label for="campus_id">Campus</label><select name="campus_id" id="campus_id" class="form-control"><option value="">Todos os permitidos</option>@foreach($campi as $campus)<option value="{{ $campus->id }}" @selected(($filtros['campus_id'] ?? '') == $campus->id)>{{ $campus->nome }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label for="curso_id">Curso</label><select name="curso_id" id="curso_id" class="form-control"><option value="">Todos os permitidos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(($filtros['curso_id'] ?? '') == $curso->id)>{{ $curso->nome }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label for="turma_id">Turma</label><select name="turma_id" id="turma_id" class="form-control"><option value="">Todas as permitidas</option>@foreach($turmas as $turma)<option value="{{ $turma->id }}" @selected(($filtros['turma_id'] ?? '') == $turma->id)>{{ $turma->curso->sigla ?: $turma->curso->nome }} · {{ $turma->nome_amigavel }}</option>@endforeach</select></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4"><label for="tipo_aplicacao">Tipo de aplicação</label><select name="tipo_aplicacao" id="tipo_aplicacao" class="form-control"><option value="">Todos</option><option value="diagnostico" @selected(($filtros['tipo_aplicacao'] ?? '') === 'diagnostico')>Diagnóstico</option><option value="monitoramento" @selected(($filtros['tipo_aplicacao'] ?? '') === 'monitoramento')>Monitoramento</option></select></div>
                <div class="form-group col-md-4"><label for="data_inicio">Abertas desde</label><input type="date" id="data_inicio" name="data_inicio" class="form-control" value="{{ $filtros['data_inicio'] ?? '' }}"></div>
                <div class="form-group col-md-4"><label for="data_fim">Abertas até</label><input type="date" id="data_fim" name="data_fim" class="form-control" value="{{ $filtros['data_fim'] ?? '' }}"></div>
            </div>
            <button type="submit" class="btn btn-outline-primary mb-3" id="report-filter">Atualizar lista de aplicações</button>
        </div>
        </details>
        <div class="card-body">
            <fieldset><legend class="h6 font-weight-bold">Aplicações que entrarão no relatório</legend>
                <p class="small text-muted">Selecione até 100 aplicações. Uma seleção única gera o modo individual. Para o histórico, escolha a mesma turma e tipo.</p>
                <div class="mb-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="report-select-all">Selecionar todas</button> <button type="button" class="btn btn-sm btn-link" id="report-select-none">Limpar seleção</button> <span class="small" id="report-selection-count" aria-live="polite"></span></div>
                <div class="report-selection border rounded p-2">
                    @forelse($opcoes as $opcao)
                        <label class="report-option d-flex p-2 border-bottom font-weight-normal mb-0" for="aplicacao-{{ $opcao->id }}"><input type="checkbox" name="aplicacoes[]" id="aplicacao-{{ $opcao->id }}" value="{{ $opcao->id }}" class="mr-2 mt-1 report-check" @checked(in_array($opcao->id, $filtros['aplicacoes'] ?? []))><span><strong>{{ $opcao->nome }}</strong><span class="d-block small text-muted">{{ $opcao->turma->curso->nome }} · {{ $opcao->turma->nome_amigavel }} · {{ $opcao->data_abertura?->format('d/m/Y') ?? 'Sem data' }} · {{ ucfirst($opcao->tipo_aplicacao->value) }} · {{ $opcao->status === App\StatusAplicacao::Aberta ? 'Aberta — parcial' : 'Encerrada' }}</span></span></label>
                    @empty<p class="text-muted mb-0">Nenhuma aplicação aberta ou encerrada corresponde aos filtros.</p>@endforelse
                </div>
            </fieldset>
        </div>
        <div class="card-footer"><button name="gerar" value="1" class="btn btn-primary">Gerar relatório</button><a href="{{ route('relatorios.index', ['relatorio' => $relatorio]) }}" class="btn btn-link">Limpar filtros</a></div>
    </form>

    @if($dados)
        <div class="d-flex justify-content-between flex-wrap align-items-start mb-3"><div><h2 class="h4">{{ $titulos[$relatorio] }}</h2><p class="small text-muted mb-0">Modo {{ $modo }} · Gerado em {{ $geradoEm->format('d/m/Y H:i') }} ({{ config('app.timezone') }}) · {{ $selecionadas->count() }} aplicação(ões)</p></div><div class="report-no-print"><a href="{{ route('relatorios.export', [...$filtros, 'gerar' => 1]) }}" class="btn btn-outline-primary mb-2">Exportar CSV</a> <button type="button" id="report-print" class="btn btn-primary mb-2">Imprimir / salvar PDF</button></div></div>
        <div class="card"><div class="card-body"><h3 class="h6">Aplicações selecionadas</h3><ul class="small mb-0">@foreach($selecionadas as $item)<li>{{ $item->nome }} · {{ $item->turma->curso->nome }} · {{ $item->turma->nome_amigavel }} · {{ $item->data_abertura?->format('d/m/Y') ?? 'Sem data' }} · {{ $item->status === App\StatusAplicacao::Aberta ? 'PARCIAL' : 'Encerrada' }}</li>@endforeach</ul></div></div>
        @foreach($dados['avisos'] as $aviso)<div class="alert alert-warning">{{ $aviso }}</div>@endforeach
        <div class="row">
            @foreach(['estudantes_unicos' => 'Estudantes únicos convidados', 'participacoes' => 'Participações previstas', 'finalizadas' => 'Participações finalizadas', 'respondentes_unicos' => 'Respondentes únicos'] as $campo => $titulo)
                <div class="col-6 col-lg-3"><div class="card"><div class="card-body"><div class="small text-muted">{{ $titulo }}</div><strong class="h3">{{ $dados['resumo'][$campo] }}</strong></div></div></div>
            @endforeach
        </div>
        <p class="small text-muted">Uma pessoa pode participar de várias aplicações. Resultados quantitativos consideram apenas participações finalizadas. Médias e distribuições com menos de {{ App\Services\IndicadoresPesquisa::MIN_PARTICIPANTES }} estudantes distintos são restritas. As médias consolidadas são ponderadas pela quantidade de respostas válidas.</p>

        @if(in_array($relatorio, ['resultado', 'historico']))
            <div class="card"><div class="card-header"><h3 class="card-title">{{ $relatorio === 'historico' ? 'Evolução por dimensão' : 'Médias por dimensão e bloco' }}</h3></div><div class="card-body">
                @forelse($dados['dimensoes']->groupBy(fn ($linha) => $linha['origem'].':'.$linha['dimensao_id'].':'.$linha['minimo'].':'.$linha['maximo']) as $grupo)
                    @php($primeira = $grupo->first())
                    @if($relatorio === 'historico' && $modo !== 'consolidado')
                        @php($pontos = $selecionadas->map(fn ($aplicacao) => ['grupo' => app(App\Services\RelatoriosPesquisa::class)->rotulo($aplicacao), 'media' => $grupo->firstWhere('aplicacao_id', $aplicacao->id)['media'] ?? null])->all())
                        <x-relatorios.linha :pontos="$pontos" :titulo="ucfirst($primeira['origem']).' · '.$primeira['dimensao']" :minimo="$primeira['minimo']" :maximo="$primeira['maximo']" />
                    @else
                        <div class="report-chart mb-4"><h4 class="h6 font-weight-bold">{{ ucfirst($primeira['origem']) }} · {{ $primeira['dimensao'] }} <small>(escala {{ $primeira['minimo'] }} a {{ $primeira['maximo'] }})</small></h4>
                            @foreach($grupo as $linha)<div class="mb-3"><div class="small mb-1">{{ $linha['grupo'] }} — <strong>{{ $linha['media'] === null ? 'Restrita' : number_format($linha['media'], 2, ',', '.') }}</strong></div>@if($linha['media'] !== null)<svg viewBox="0 0 640 20" class="report-bar" role="img" aria-label="Média {{ number_format($linha['media'], 2, ',', '.') }} em uma escala de {{ $linha['minimo'] }} a {{ $linha['maximo'] }}"><rect width="640" height="20" fill="#e2e8f0"/><rect width="{{ $linha['media'] * 640 / max(1, $linha['maximo']) }}" height="20" fill="#087e8b"/></svg>@endif</div>@endforeach
                        </div>
                    @endif
                @empty<p class="text-muted">Não há resultados quantitativos comparáveis para exibir.</p>@endforelse
            </div></div>
            <details class="card report-disclosure report-print-expand"><summary class="card-header"><span class="font-weight-bold">Detalhamento das dimensões</span><span class="report-toggle-hint small text-muted"><span class="report-when-closed">Exibir tabela</span><span class="report-when-open">Recolher tabela</span></span></summary><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Aplicação / grupo</th><th>Bloco</th><th>Dimensão</th><th>Escala</th><th>Estudantes</th><th>Respostas</th><th>Média</th></tr></thead><tbody>@forelse($dados['dimensoes'] as $linha)<tr><td>{{ $linha['grupo'] }}</td><td>{{ ucfirst($linha['origem']) }}</td><td>{{ $linha['dimensao'] }}</td><td>{{ $linha['minimo'] }} a {{ $linha['maximo'] }}</td><td>{{ $linha['estudantes'] }}</td><td>{{ $linha['respostas'] }}</td><td>{{ $linha['media'] === null ? 'Restrita' : number_format($linha['media'], 2, ',', '.') }}</td></tr>@empty<tr><td colspan="7">Sem respostas finalizadas comparáveis.</td></tr>@endforelse</tbody></table></div></details>
            <div class="card"><div class="card-header"><h3 class="card-title">Distribuição e médias por pergunta</h3></div><div class="card-body">
                <p class="small text-muted">Cada segmento representa a proporção de respostas com aquela nota. Valores menores indicam percepção menos favorável. As escalas originais são preservadas.</p>
                @forelse($dados['perguntas'] as $linha)
                    <div class="report-chart border-bottom mb-3 pb-3"><h4 class="h6">{{ $linha['texto'] }}</h4><p class="small mb-1">{{ ucfirst($linha['origem']) }} · {{ $linha['grupo'] }} · {{ $linha['estudantes'] }} estudante(s) · {{ $linha['respostas'] }} resposta(s) · Média: <strong>{{ $linha['media'] === null ? 'Restrita' : number_format($linha['media'], 2, ',', '.') }}</strong> · Escala {{ $linha['minimo'] }} a {{ $linha['maximo'] }}</p>
                    @if($linha['distribuicao'] === null)<p class="small text-muted mb-0">Distribuição restrita pelo mínimo de participantes.</p>@else
                        <div class="report-stacked" role="img" aria-label="Distribuição das notas de {{ $linha['minimo'] }} a {{ $linha['maximo'] }}">
                            @foreach($linha['distribuicao'] as $nota => $quantidade)<span style="width:{{ $quantidade * 100 / max(1, $linha['respostas']) }}%;background:hsl({{ 20 + ($nota - $linha['minimo']) * 140 / max(1, $linha['maximo'] - $linha['minimo']) }},55%,35%)" title="Nota {{ $nota }}: {{ $quantidade }} resposta(s)"></span>@endforeach
                        </div>
                        <p class="small mt-1 mb-0">@for($nota = $linha['minimo']; $nota <= $linha['maximo']; $nota++)<span class="mr-3">Nota {{ $nota }}: {{ $linha['distribuicao'][$nota] ?? 0 }} ({{ number_format(($linha['distribuicao'][$nota] ?? 0) * 100 / max(1, $linha['respostas']), 1, ',', '.') }}%)</span>@endfor</p>
                    @endif</div>
                @empty<p class="text-muted">Sem respostas finalizadas comparáveis.</p>@endforelse
            </div></div>
        @elseif($relatorio === 'participacao')
            <div class="card"><div class="card-body"><x-relatorios.barras :linhas="$dados['participacao']" titulo="Situação das participações" :series="[['campo' => 'finalizadas', 'nome' => 'Finalizadas', 'cor' => '#087e8b'], ['campo' => 'andamento', 'nome' => 'Em andamento', 'cor' => '#b7791f'], ['campo' => 'nao_iniciadas', 'nome' => 'Não iniciadas', 'cor' => '#64748b']]" /></div></div>
            @if($modo === 'comparativo')<div class="card"><div class="card-body"><x-relatorios.linha :pontos="$dados['participacao']->map(fn ($linha) => ['grupo' => $linha['grupo'], 'media' => $linha['taxa']])->all()" titulo="Taxa de conclusão por aplicação, em ordem de abertura (%)" :minimo="0" :maximo="100" /></div></div>@endif
        @else
            <div class="row"><div class="col-lg-6"><div class="card"><div class="card-body"><x-relatorios.barras :linhas="$dados['solicitacoes']" titulo="Situação das solicitações" :series="[['campo' => 'pendentes', 'nome' => 'Pendentes', 'cor' => '#b7791f'], ['campo' => 'atendidas', 'nome' => 'Atendidas', 'cor' => '#087e8b']]" /></div></div></div>
            <div class="col-lg-6"><div class="card"><div class="card-body"><x-relatorios.barras :linhas="$dados['solicitacoes']" titulo="Tempo de espera das solicitações pendentes" :series="[['campo' => 'ate_7', 'nome' => 'Até 7 dias', 'cor' => '#087e8b'], ['campo' => 'de_8_a_14', 'nome' => '8 a 14 dias', 'cor' => '#b7791f'], ['campo' => 'acima_14', 'nome' => '15 dias ou mais', 'cor' => '#a33a45']]" /></div></div></div></div>
            <div class="card"><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Aplicação / grupo</th><th>Pendentes</th><th>Atendidas</th><th>Espera média pendente (dias)</th><th>Tempo médio até atendimento (dias)</th></tr></thead><tbody>@foreach($dados['solicitacoes'] as $linha)<tr><td>{{ $linha['grupo'] }}</td><td>{{ $linha['pendentes'] }}</td><td>{{ $linha['atendidas'] }}</td><td>{{ $linha['espera_media'] ?? '—' }}</td><td>{{ $linha['atendimento_medio'] ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>
            <p class="small text-muted">A espera é calculada na data de geração. As solicitações são manifestações voluntárias dos estudantes. O relatório não apresenta notas individuais nem prontuários.</p><a class="btn btn-outline-primary mb-3 report-no-print" href="{{ route('solicitacoes.index') }}">Abrir lista de atendimento</a>
        @endif
        <div class="card"><div class="card-header"><h3 class="card-title">Participação nas aplicações selecionadas</h3></div><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Aplicação / grupo</th><th>Previstas</th><th>Finalizadas</th><th>Em andamento</th><th>Não iniciadas</th><th>Conclusão</th></tr></thead><tbody>@foreach($dados['participacao'] as $linha)<tr><td>{{ $linha['grupo'] }}</td><td>{{ $linha['previstas'] }}</td><td>{{ $linha['finalizadas'] }}</td><td>{{ $linha['andamento'] }}</td><td>{{ $linha['nao_iniciadas'] }}</td><td>{{ number_format($linha['taxa'], 1, ',', '.') }}%</td></tr>@endforeach</tbody></table></div></div>
    @endif
@stop

@section('css')
<style>
.report-disclosure > summary { display: flex; align-items: center; gap: .75rem; cursor: pointer; list-style: none; }
.report-disclosure > summary::-webkit-details-marker { display: none; }
.report-disclosure > summary::before { content: '\203A'; font-size: 1.4rem; line-height: 1; transition: transform .15s; }
.report-disclosure[open] > summary::before { transform: rotate(90deg); }
.report-disclosure > summary:focus-visible { outline: 2px solid #087e8b; outline-offset: -2px; }
.report-toggle-hint { margin-left: auto; text-align: right; }
.report-disclosure .report-when-open, .report-disclosure[open] .report-when-closed { display: none; }
.report-disclosure[open] .report-when-open { display: inline; }
.report-selection { max-height: 360px; overflow-y: auto; }
.report-option { cursor: pointer; }
.report-option:hover { background: #f1f5f9; }
.report-stacked { display: flex; min-height: 24px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.report-stacked span { display: block; min-height: 24px; }
.report-key { display: inline-block; width: 12px; height: 12px; border-radius: 2px; }
.report-line, .report-bar { display: block; width: 100%; height: auto; }
.report-line { max-height: 260px; }
.report-chart { break-inside: avoid; }
@media print {
    .main-sidebar, .main-header, .main-footer, .content-header, .report-no-print, .control-sidebar { display: none !important; }
    .content-wrapper, .main-footer { margin: 0 !important; }
    .content-wrapper { background: white !important; }
    .card { box-shadow: none !important; border: 1px solid #bbb; }
    .card-body, .table-responsive { overflow: visible !important; }
    .report-print-expand > summary::before, .report-toggle-hint { display: none !important; }
    .report-stacked, .report-stacked span, .report-key, .alert { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    table { font-size: 10px; } tr { break-inside: avoid; } thead { display: table-header-group; }
    a[href]::after { content: none !important; }
    @page { size: A4; margin: 12mm; }
}
</style>
@stop

@section('js')
<script>
(() => {
    const checks = [...document.querySelectorAll('.report-check')];
    const update = () => { document.getElementById('report-selection-count').textContent = `${checks.filter(c => c.checked).length} selecionada(s)`; };
    checks.forEach(c => c.addEventListener('change', update));
    document.getElementById('report-select-all').addEventListener('click', () => { checks.forEach((c, i) => c.checked = i < 100); update(); });
    document.getElementById('report-select-none').addEventListener('click', () => { checks.forEach(c => c.checked = false); update(); });
    document.getElementById('report-filter').addEventListener('click', () => { checks.forEach(c => c.checked = false); });
    document.getElementById('report-print')?.addEventListener('click', () => window.print());
    const printDetails = [...document.querySelectorAll('.report-print-expand')];
    let closedBeforePrint = [];
    window.addEventListener('beforeprint', () => {
        closedBeforePrint = printDetails.filter(detail => !detail.open);
        closedBeforePrint.forEach(detail => detail.open = true);
    });
    window.addEventListener('afterprint', () => {
        closedBeforePrint.forEach(detail => detail.open = false);
        closedBeforePrint = [];
    });
    update();
})();
</script>
@stop
