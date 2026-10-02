@extends('adminlte::page')

@section('title', 'Editar permissão')

@section('content_header')
    <h1 class="m-0">Editar permissão</h1>
@stop

@section('content')
    <div class="container-fluid"><div class="card card-outline card-primary"><div class="card-body">
        <p>Servidor: <strong>{{ $usuarioPermissao->usuario->name }}</strong></p>
        <form action="{{ route('permissoes.details.update', $usuarioPermissao) }}" method="POST">
            @csrf @method('PUT')
            <x-form-errors />
            <x-servidores.permissao-fields :$campi :$cursos :permissao="$usuarioPermissao" />
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar permissão</button>
            <a href="{{ route('servidores.edit', $usuarioPermissao->usuario_id) }}" class="btn btn-default">Cancelar</a>
        </form>
    </div></div></div>
@stop
