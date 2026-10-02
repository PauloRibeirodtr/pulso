@extends('adminlte::page')
@php($editando = isset($dimensao))
@section('title', $editando ? 'Editar dimensão' : 'Nova dimensão')
@section('content_header')<h1>{{ $editando ? 'Editar dimensão' : 'Nova dimensão' }}</h1>@stop
@section('content')
    <x-form-errors />
    <form method="POST" action="{{ $editando ? route('dimensoes.update', $dimensao) : route('dimensoes.store') }}">
        @csrf @if($editando) @method('PUT') @endif
        <div class="card card-outline card-primary"><div class="card-body">
            <div class="form-group"><label for="nome">Nome</label><input id="nome" name="nome" class="form-control" required maxlength="150" value="{{ old('nome', $dimensao->nome ?? '') }}"></div>
            <div class="form-group"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" class="form-control" rows="3">{{ old('descricao', $dimensao->descricao ?? '') }}</textarea></div>
            <div class="form-group"><label for="ativo">Situação</label><select id="ativo" name="ativo" class="form-control"><option value="1" @selected(old('ativo', $dimensao->ativo ?? true))>Ativa</option><option value="0" @selected(!old('ativo', $dimensao->ativo ?? true))>Inativa</option></select></div>
        </div><div class="card-footer"><button class="btn btn-primary">Salvar</button> <a class="btn btn-link" href="{{ route('dimensoes.index') }}">Cancelar</a></div></div>
    </form>
@stop
