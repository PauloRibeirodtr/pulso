@extends('adminlte::page')

@section('title', 'Editar estudante')

@section('content_header')
    <h1 class="m-0">Editar estudante</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary"><div class="card-body">
            <x-estudantes.form :action="route('estudantes.update', $estudante)" method="PUT" :$turmas :$estudante />
        </div></div>
    </div>
@stop
