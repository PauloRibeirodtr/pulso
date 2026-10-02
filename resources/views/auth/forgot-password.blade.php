@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('auth_header', 'Recuperar senha')

@section('auth_body')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <p class="text-muted">
        Esta recuperação é exclusiva para servidores. Informe seu e-mail institucional para receber o link de redefinição.
    </p>

    <form action="{{ route('password.email') }}" method="POST">
        @csrf

        <div class="input-group mb-3">
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                placeholder="E-mail"
                required
                autofocus
            >

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>

            @error('email')
                <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <button
            type="submit"
            class="btn btn-primary btn-block"
        >
            Enviar link
        </button>
    </form>
@stop

@section('auth_footer')
    <a href="{{ route('login') }}">
        Voltar para o login
    </a>
@stop
