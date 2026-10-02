@extends('adminlte::page')

@section('title', 'Editar curso')

@section('content_header')
    <h1 class="m-0">Editar curso</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-cursos.form
                    :action="route('cursos.update', $curso)"
                    :campi="$campi"
                    method="PUT"
                    :curso="$curso"
                />
            </div>
        </div>
    </div>
@stop
