@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('auth_header', 'Confirme sua senha')

@section('auth_body')
    <p class="text-muted">
        Confirme sua senha antes de continuar.
    </p>

    <form action="{{ route('password.confirm.store') }}" method="POST">
        @csrf

        <div class="input-group mb-3">
            <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Senha"
                required
                autofocus
                autocomplete="current-password"
            >

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>

            @error('password')
                <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            Confirmar senha
        </button>
    </form>
@stop
