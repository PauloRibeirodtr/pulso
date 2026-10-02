@extends('adminlte::page')

@section('title', 'Novo campus')

@section('content_header')
    <h1 class="m-0">Novo campus</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-body">
                <x-campi.form :action="route('campi.store')" />
            </div>
        </div>
    </div>
@stop
