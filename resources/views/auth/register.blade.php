@extends('adminlte::auth.auth-page', [
    'authType' => 'register',
])

@section('auth_header', 'Criar conta no Pulso IFMS')

@section('auth_body')
    <form action="{{ route('register') }}" method="POST">
        @csrf

        <div class="input-group mb-3">
            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
                class="form-control @error('name') is-invalid @enderror"
                placeholder="Nome"
                required
                autofocus
                autocomplete="name"
            >

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-user"></span>
                </div>
            </div>

            @error('name')
                <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                placeholder="E-mail"
                required
                autocomplete="email"
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

        <div class="input-group mb-3">
            <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Senha"
                required
                autocomplete="new-password"
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

        <div class="input-group mb-3">
            <input
                type="password"
                name="password_confirmation"
                class="form-control"
                placeholder="Confirme a senha"
                required
                autocomplete="new-password"
            >

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
        </div>

        <button
            type="submit"
            class="btn btn-primary btn-block"
        >
            Cadastrar
        </button>
    </form>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('login') }}">
            Já tenho uma conta
        </a>
    </p>
@stop
