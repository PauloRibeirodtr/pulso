@extends('adminlte::page')

@section('title', 'Novo estudante')

@section('content_header')
    <h1 class="m-0">Novo estudante</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary"><div class="card-body">
            <x-estudantes.form :action="route('estudantes.store')" :$turmas />
        </div></div>
    </div>
@stop
