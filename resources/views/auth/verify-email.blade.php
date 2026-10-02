@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('auth_header', 'Verifique seu e-mail')

@section('auth_body')
    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            Um novo link de verificação foi enviado para seu e-mail.
        </div>
    @endif

    <p class="text-muted">
        Antes de continuar, acesse o link de verificação enviado para seu e-mail.
    </p>

    <form action="{{ route('verification.send') }}" method="POST">
        @csrf

        <button type="submit" class="btn btn-primary btn-block">
            Reenviar e-mail de verificação
        </button>
    </form>
@stop

@section('auth_footer')
    <form action="{{ route('logout') }}" method="POST">
        @csrf

        <button type="submit" class="btn btn-link p-0">
            Sair
        </button>
    </form>
@stop
