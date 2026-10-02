@extends('adminlte::page')

@section('title', 'Editar campus')

@section('content_header')
    <h1 class="m-0">Editar campus</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-campi.form
                    :action="route('campi.update', $campus)"
                    method="PUT"
                    :campus="$campus"
                />
            </div>
        </div>
    </div>
@stop
