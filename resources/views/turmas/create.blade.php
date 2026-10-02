@extends('adminlte::page')

@section('title', 'Nova turma')

@section('content_header')
    <h1 class="m-0">Nova turma</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-turmas.form :action="route('turmas.store')" :$cursos />
            </div>
        </div>
    </div>
@stop
