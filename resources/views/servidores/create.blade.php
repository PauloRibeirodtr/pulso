@extends('adminlte::page')

@section('title', 'Novo servidor')

@section('content_header')
    <h1 class="m-0">Novo servidor</h1>
@stop

@section('content')
    <div class="container-fluid"><div class="card card-outline card-primary"><div class="card-body">
        <x-servidores.form :action="route('servidores.store')" :$campi :$cursos />
    </div></div></div>
@stop
