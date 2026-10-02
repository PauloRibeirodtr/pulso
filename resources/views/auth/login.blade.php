@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('auth_header', 'Acessar o Pulso IFMS')

@section('auth_body')
    <form action="{{ route('login.store') }}" method="POST">
        @csrf

        <label for="identificador_institucional">Matrícula ou SIAPE</label>
        <div class="input-group mb-3">
            <input
                id="identificador_institucional"
                type="text"
                name="identificador_institucional"
                value="{{ old('identificador_institucional') }}"
                class="form-control @error('identificador_institucional') is-invalid @enderror"
                placeholder="Matrícula ou SIAPE"
                required
                autofocus
                autocomplete="username"
            >

            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-id-card"></span>
                </div>
            </div>

            @error('identificador_institucional')
                <span class="invalid-feedback">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <label for="password">Senha</label>
        <div class="input-group mb-3">
            <input
                id="password"
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Senha"
                required
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

        <div class="row">
            <div class="col-8">
                <div class="icheck-primary">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                    >

                    <label for="remember">
                        Lembrar-me
                    </label>
                </div>
            </div>

            <div class="col-4">
                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Entrar
                </button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    <p class="my-0">
        <a href="{{ route('password.request') }}">
            Recuperar senha de servidor
        </a>
    </p>
@stop
