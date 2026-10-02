@extends('adminlte::page')
@php
    $aplicacao = $aplicacao ?? null;
    $editando = isset($aplicacao);
    $selecionados = $editando ? $aplicacao->perguntas->where('origem', App\OrigemPerguntaAplicacao::Temporaria)->pluck('pergunta_id')->all() : [];
    $institucionalId = $editando ? $aplicacao->modelosQuestionario->firstWhere('escopo_modelo', App\EscopoModeloQuestionario::Institucional)?->id : null;
    $cursoId = $editando ? $aplicacao->modelosQuestionario->firstWhere('escopo_modelo', App\EscopoModeloQuestionario::Curso)?->id : null;
@endphp
@section('title', $editando ? 'Editar aplicação' : 'Nova aplicação')
@section('content_header')<h1>{{ $editando ? 'Editar aplicação planejada' : 'Nova aplicação de pesquisa' }}</h1>@stop
@section('content')
    <x-form-errors />
    <form method="POST" action="{{ $editando ? route('aplicacoes.update', $aplicacao) : route('aplicacoes.store') }}">@csrf @if($editando) @method('PUT') @endif
        <div class="card card-outline card-primary"><div class="card-body">
            <p class="text-muted">Selecione uma turma, um modelo institucional e um modelo permanente do curso, todos já publicados. Perguntas temporárias são opcionais.</p>
            <div class="form-group"><label for="nome">Nome da aplicação</label><input id="nome" name="nome" class="form-control" maxlength="150" required value="{{ old('nome', $aplicacao->nome ?? '') }}"></div>
            <div class="form-group"><label for="turma_id">Turma</label><select id="turma_id" name="turma_id" class="form-control" required @if($editando) disabled @endif><option value="">Selecione</option>@foreach($turmas as $turma)<option value="{{ $turma->id }}" @selected(old('turma_id', $aplicacao->turma_id ?? '') == $turma->id)>{{ $turma->curso->sigla ?: $turma->curso->nome }} — {{ $turma->nome_amigavel }}</option>@endforeach</select>@if($editando)<input type="hidden" name="turma_id" value="{{ $aplicacao->turma_id }}">@endif</div>
            <div class="form-group"><label for="tipo_aplicacao">Tipo</label><select id="tipo_aplicacao" name="tipo_aplicacao" class="form-control"><option value="diagnostico" @selected(old('tipo_aplicacao', $aplicacao->tipo_aplicacao->value ?? '') === 'diagnostico')>Diagnóstico</option><option value="monitoramento" @selected(old('tipo_aplicacao', $aplicacao->tipo_aplicacao->value ?? '') === 'monitoramento')>Monitoramento</option></select></div>
            <div class="form-group"><label for="institucional_modelo_id">Modelo institucional</label><select id="institucional_modelo_id" name="institucional_modelo_id" class="form-control" required><option value="">Selecione</option>@foreach($modelos->where('escopo_modelo', App\EscopoModeloQuestionario::Institucional) as $modelo)<option value="{{ $modelo->id }}" @selected(old('institucional_modelo_id', $institucionalId) == $modelo->id)>{{ $modelo->nome }} ({{ ucfirst($modelo->tipo->value) }})</option>@endforeach</select></div>
            <div class="form-group"><label for="curso_modelo_id">Modelo permanente do curso</label><select id="curso_modelo_id" name="curso_modelo_id" class="form-control" required><option value="">Selecione</option>@foreach($modelos->where('escopo_modelo', App\EscopoModeloQuestionario::Curso) as $modelo)<option value="{{ $modelo->id }}" @selected(old('curso_modelo_id', $cursoId) == $modelo->id)>{{ $modelo->curso?->sigla ?: $modelo->curso?->nome }} — {{ $modelo->nome }} ({{ ucfirst($modelo->tipo->value) }})</option>@endforeach</select></div>
            <div class="form-group"><label for="temporarias">Perguntas temporárias do curso</label><select id="temporarias" name="temporarias[]" class="form-control" multiple size="6">@foreach($temporariasDisponiveis as $pergunta)<option value="{{ $pergunta->id }}" @selected(in_array($pergunta->id, old('temporarias', $selecionados)))>{{ $pergunta->cursoOrigem?->sigla ?: $pergunta->cursoOrigem?->nome }} — {{ $pergunta->texto }}</option>@endforeach</select><small class="form-text text-muted">Use Ctrl ou Command para selecionar mais de uma. As temporárias são opcionais.</small></div>
            <div class="form-row"><div class="form-group col-md-6"><label for="data_abertura">Abertura prevista</label><input id="data_abertura" type="datetime-local" name="data_abertura" class="form-control" value="{{ old('data_abertura', $aplicacao?->data_abertura?->format('Y-m-d\TH:i') ?? '') }}"></div><div class="form-group col-md-6"><label for="data_encerramento">Encerramento previsto</label><input id="data_encerramento" type="datetime-local" name="data_encerramento" class="form-control" value="{{ old('data_encerramento', $aplicacao?->data_encerramento?->format('Y-m-d\TH:i') ?? '') }}"></div></div>
        </div><div class="card-footer"><button class="btn btn-primary">Salvar planejamento</button> <a class="btn btn-link" href="{{ $editando ? route('aplicacoes.show', $aplicacao) : route('aplicacoes.index') }}">Cancelar</a></div></div>
    </form>
@stop
