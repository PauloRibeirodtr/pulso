@extends('adminlte::page')
@php($editando = isset($pergunta))
@section('title', $editando ? 'Editar pergunta' : 'Nova pergunta')
@section('content_header')<h1>{{ $editando ? 'Editar pergunta' : 'Nova pergunta' }}</h1>@stop
@section('content')
    <x-form-errors />
    <form method="POST" action="{{ $editando ? route('perguntas.update', $pergunta) : route('perguntas.store') }}">@csrf @if($editando) @method('PUT') @endif
        <div class="card card-outline card-primary"><div class="card-body">
            <div class="form-group"><label for="texto">Enunciado</label><textarea id="texto" name="texto" class="form-control" rows="3" required maxlength="2000">{{ old('texto', $pergunta->texto ?? '') }}</textarea></div>
            <div class="form-group"><label for="dimensao_id">Dimensão</label><select id="dimensao_id" name="dimensao_id" class="form-control" required><option value="">Selecione</option>@foreach($dimensoes as $dimensao)<option value="{{ $dimensao->id }}" @selected(old('dimensao_id', $pergunta->dimensao_id ?? '') == $dimensao->id)>{{ $dimensao->nome }}</option>@endforeach</select></div>
            <div class="form-group"><label for="curso_origem_id">Bloco da pergunta</label><select id="curso_origem_id" name="curso_origem_id" class="form-control" @if($editando) disabled @endif>
                @if(auth()->user()->possuiPermissaoInstitucional())<option value="">Institucional</option>@endif
                @foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(old('curso_origem_id', $pergunta->curso_origem_id ?? '') == $curso->id)>{{ $curso->sigla ?: $curso->nome }}</option>@endforeach
            </select>@if($editando)<input type="hidden" name="curso_origem_id" value="{{ $pergunta->curso_origem_id }}">@endif</div>
            <p class="text-muted">Escala fixa de 1 a 5; valores menores indicam maior atenção.</p>
            <div class="form-group"><label for="sensivel">Pergunta sensível</label><select id="sensivel" name="sensivel" class="form-control"><option value="0" @selected(!old('sensivel', $pergunta->sensivel ?? false))>Não</option><option value="1" @selected(old('sensivel', $pergunta->sensivel ?? false))>Sim</option></select></div>
            <div class="form-group"><label for="ativo">Situação</label><select id="ativo" name="ativo" class="form-control"><option value="1" @selected(old('ativo', $pergunta->ativo ?? true))>Ativa</option><option value="0" @selected(!old('ativo', $pergunta->ativo ?? true))>Inativa</option></select></div>
        </div><div class="card-footer"><button class="btn btn-primary">Salvar</button> <a class="btn btn-link" href="{{ route('perguntas.index') }}">Cancelar</a></div></div>
    </form>
@stop
