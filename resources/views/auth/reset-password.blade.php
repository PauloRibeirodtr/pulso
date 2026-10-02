@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('auth_header', 'Definir nova senha')

@section('auth_body')
    <p class="text-muted">
        Recuperação disponível somente para contas de servidores.
    </p>

    <form action="{{ route('password.update') }}" method="POST">
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <div class="input-group mb-3">
            <input
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                class="form-control @error('email') is-invalid @enderror"
                placeholder="E-mail"
                required
            >

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
                placeholder="Nova senha"
                required
            >

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
                placeholder="Confirme a nova senha"
                required
            >
        </div>

        <button
            type="submit"
            class="btn btn-primary btn-block"
        >
            Alterar senha
        </button>
    </form>
@stop
