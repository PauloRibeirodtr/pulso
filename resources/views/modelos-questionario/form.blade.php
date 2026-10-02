@extends('adminlte::page')
@php($editando = isset($modeloQuestionario))
@section('title', $editando ? 'Editar modelo' : 'Novo modelo')
@section('content_header')<h1>{{ $editando ? 'Editar modelo' : 'Novo modelo' }}</h1>@stop
@section('content')
    <x-form-errors />
    <form method="POST" action="{{ $editando ? route('modelos-questionario.update', $modeloQuestionario) : route('modelos-questionario.store') }}">@csrf @if($editando) @method('PUT') @endif
        <div class="card card-outline card-primary"><div class="card-body">
            <div class="form-group"><label for="nome">Nome</label><input id="nome" name="nome" class="form-control" required maxlength="150" value="{{ old('nome', $modeloQuestionario->nome ?? '') }}"></div>
            <div class="form-group"><label for="escopo_modelo">Bloco</label><select id="escopo_modelo" name="escopo_modelo" class="form-control" @if($editando) disabled @endif>
                @if(auth()->user()->possuiPermissaoInstitucional())<option value="institucional" @selected(old('escopo_modelo', $modeloQuestionario->escopo_modelo->value ?? '') === 'institucional')>Institucional</option>@endif
                <option value="curso" @selected(old('escopo_modelo', $modeloQuestionario->escopo_modelo->value ?? '') === 'curso')>Permanente do curso</option>
            </select>@if($editando)<input type="hidden" name="escopo_modelo" value="{{ $modeloQuestionario->escopo_modelo->value }}">@endif</div>
            <div class="form-group"><label for="curso_id">Curso (deixe vazio para institucional)</label><select id="curso_id" name="curso_id" class="form-control" @if($editando) disabled @endif><option value="">Sem curso</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(old('curso_id', $modeloQuestionario->curso_id ?? '') == $curso->id)>{{ $curso->sigla ?: $curso->nome }}</option>@endforeach</select>@if($editando)<input type="hidden" name="curso_id" value="{{ $modeloQuestionario->curso_id }}">@endif</div>
            <div class="form-group"><label for="tipo">Tipo</label><select id="tipo" name="tipo" class="form-control" @if($editando) disabled @endif><option value="diagnostico" @selected(old('tipo', $modeloQuestionario->tipo->value ?? '') === 'diagnostico')>Diagnóstico</option><option value="monitoramento" @selected(old('tipo', $modeloQuestionario->tipo->value ?? '') === 'monitoramento')>Monitoramento</option></select>@if($editando)<input type="hidden" name="tipo" value="{{ $modeloQuestionario->tipo->value }}">@endif</div>
            <div class="form-group"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" class="form-control" rows="3">{{ old('descricao', $modeloQuestionario->descricao ?? '') }}</textarea></div>
            <div class="form-group"><label for="ativo">Situação</label><select id="ativo" name="ativo" class="form-control"><option value="1" @selected(old('ativo', $modeloQuestionario->ativo ?? true))>Ativo</option><option value="0" @selected(!old('ativo', $modeloQuestionario->ativo ?? true))>Inativo</option></select></div>
        </div><div class="card-footer"><button class="btn btn-primary">Salvar</button> <a class="btn btn-link" href="{{ $editando ? route('modelos-questionario.show', $modeloQuestionario) : route('modelos-questionario.index') }}">Cancelar</a></div></div>
    </form>
@stop
