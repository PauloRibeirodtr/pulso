@extends('adminlte::page')

@section('title', 'Novo curso')

@section('content_header')
    <h1 class="m-0">Novo curso</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-cursos.form :action="route('cursos.store')" :campi="$campi" />
            </div>
        </div>
    </div>
@stop
