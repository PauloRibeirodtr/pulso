@extends('adminlte::page')

@section('title', 'Editar turma')

@section('content_header')
    <h1 class="m-0">Editar turma</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-turmas.form
                    :action="route('turmas.update', $turma)"
                    method="PUT"
                    :$cursos
                    :$turma
                />
            </div>
        </div>
    </div>
@stop
