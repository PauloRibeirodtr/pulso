@extends('adminlte::page')

@section('title', 'Indicadores')

@section('content_header')
    <div>
        <h1 class="mb-1">Indicadores</h1>
        <p class="text-muted mb-0">Resultados agregados de participações finalizadas, conforme seu escopo de acesso.</p>
    </div>
@stop

@section('content')
    <x-form-errors />
    <div class="card card-outline card-primary"><div class="card-header"><h2 class="card-title">Filtrar aplicações</h2></div><div class="card-body">
        <form method="GET" action="{{ route('indicadores.index') }}">
            <div class="form-row">
                <div class="form-group col-md-4"><label for="campus_id">Campus</label><select id="campus_id" name="campus_id" class="form-control"><option value="">Todos os permitidos</option>@foreach($campi as $campus)<option value="{{ $campus->id }}" @selected(($filtros['campus_id'] ?? '') == $campus->id)>{{ $campus->sigla ?: $campus->nome }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label for="curso_id">Curso</label><select id="curso_id" name="curso_id" class="form-control"><option value="">Todos os permitidos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(($filtros['curso_id'] ?? '') == $curso->id)>{{ $curso->sigla ?: $curso->nome }}</option>@endforeach</select></div>
                <div class="form-group col-md-4"><label for="turma_id">Turma</label><select id="turma_id" name="turma_id" class="form-control"><option value="">Todas as permitidas</option>@foreach($turmas as $turma)<option value="{{ $turma->id }}" @selected(($filtros['turma_id'] ?? '') == $turma->id)>{{ $turma->curso->sigla ?: $turma->curso->nome }} — {{ $turma->nome_amigavel }}</option>@endforeach</select></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4"><label for="tipo_aplicacao">Tipo</label><select id="tipo_aplicacao" name="tipo_aplicacao" class="form-control"><option value="">Todos</option><option value="diagnostico" @selected(($filtros['tipo_aplicacao'] ?? '') === 'diagnostico')>Diagnóstico</option><option value="monitoramento" @selected(($filtros['tipo_aplicacao'] ?? '') === 'monitoramento')>Monitoramento</option></select></div>
                <div class="form-group col-md-4"><label for="data_inicio">Abertas desde</label><input id="data_inicio" type="date" name="data_inicio" value="{{ $filtros['data_inicio'] ?? '' }}" class="form-control"></div>
                <div class="form-group col-md-4"><label for="data_fim">Abertas até</label><input id="data_fim" type="date" name="data_fim" value="{{ $filtros['data_fim'] ?? '' }}" class="form-control"></div>
            </div>
            <button class="btn btn-primary">Aplicar filtros</button> <a href="{{ route('indicadores.index') }}" class="btn btn-link">Limpar</a>
        </form>
    </div></div>

    <div class="row">
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Aplicações no filtro</small><div class="h3 mb-0">{{ $aplicacoes->total() }}</div></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Participações finalizadas / previstas</small><div class="h3 mb-0">{{ $participacao->finalizadas ?? 0 }} / {{ $participacao->convidados ?? 0 }}</div><small class="text-muted">Contagem de participações, não de pessoas distintas.</small></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Média do bloco institucional</small><div class="h3 mb-0">{{ $indicadores->mediaPublicavel($resumoGeral) !== null ? number_format($indicadores->mediaPublicavel($resumoGeral), 2, ',', '.') : '—' }}</div><small class="text-muted">{{ $resumoGeral->estudantes }} estudante(s) distintos · escala de 1 a 5</small></div></div></div>
    </div>

    <p class="text-muted small">Médias aparecem apenas com {{ App\Services\IndicadoresPesquisa::MIN_PARTICIPANTES }} ou mais estudantes distintos. Valores menores indicam experiência menos favorável. Perguntas temporárias não integram a média institucional.</p>

    <div class="card"><div class="card-header"><h2 class="card-title">Resultados por curso</h2></div><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Curso</th><th>Estudantes distintos</th><th>Respostas institucionais</th><th>Média institucional</th></tr></thead><tbody>
        @forelse($mediasCurso as $curso)<tr><td>{{ $curso->curso_sigla ?: $curso->curso_nome }}</td><td>{{ $curso->estudantes }}</td><td>{{ $curso->respostas }}</td><td>{{ $indicadores->mediaPublicavel($curso) !== null ? number_format($indicadores->mediaPublicavel($curso), 2, ',', '.') : 'Restrita' }}</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted">Ainda não há respostas finalizadas para os filtros escolhidos.</td></tr>@endforelse
    </tbody></table></div></div>

    <div class="card"><div class="card-header"><h2 class="card-title">Aplicações e série histórica</h2></div><div class="card-body table-responsive p-0"><table class="table table-hover"><thead><tr><th>Aplicação</th><th>Curso / turma</th><th>Abertura</th><th>Participação</th><th>Média institucional</th><th>Ações</th></tr></thead><tbody>
        @forelse($aplicacoes as $aplicacao)
            @php($resumo = $mediasAplicacao->get($aplicacao->id))
            <tr><td>{{ $aplicacao->nome }} @if($aplicacao->status === App\StatusAplicacao::Aberta)<span class="badge badge-info">Parcial</span>@endif</td><td>{{ $aplicacao->turma->curso->sigla ?: $aplicacao->turma->curso->nome }} / {{ $aplicacao->turma->nome_amigavel }}</td><td>{{ $aplicacao->data_abertura?->format('d/m/Y') ?? '—' }}</td><td>{{ $aplicacao->finalizadas_count }}/{{ $aplicacao->participacoes_count }}</td><td>{{ $indicadores->mediaPublicavel($resumo) !== null ? number_format($indicadores->mediaPublicavel($resumo), 2, ',', '.') : 'Restrita' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('indicadores.show', $aplicacao) }}">Detalhar</a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted">Nenhuma aplicação encontrada.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $aplicacoes->links('pagination::bootstrap-4') }}</div></div>

    <p class="text-muted small">A média agrupa respostas, não pessoas; uma mesma pessoa pode participar de mais de uma aplicação ao longo do tempo. Comparações entre instrumentos diferentes devem ser interpretadas com cautela.</p>
@stop
