@extends('adminlte::page')

@section('title', 'Início — Pulso IFMS')

@section('content_header')
    <div>
        <h1 class="m-0">Início</h1>

        <small class="text-muted">
            Acompanhamento da permanência estudantil
        </small>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <section class="pulso-welcome" aria-labelledby="pulso-welcome-title">
            <img src="{{ asset('logo-pulso-ifms.png') }}" alt="Logo Pulso IFMS" width="150" height="106">
            <div>
                <span class="pulso-eyebrow">Pulso IFMS · Permanência estudantil</span>
                <h2 id="pulso-welcome-title">Sua voz movimenta o futuro.</h2>
                <p>Um espaço de escuta para compreender a experiência dos estudantes e apoiar sua trajetória no IFMS.</p>
            </div>
        </section>
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    Bem-vindo ao Pulso IFMS
                </h3>
            </div>

            <div class="card-body">
                <p>
                    Olá, <strong>{{ auth()->user()->name }}</strong>.
                </p>

                <a href="{{ route('password.change') }}" class="btn btn-outline-primary">
                    Alterar minha senha
                </a>
            </div>
        </div>
    </div>
@stop
