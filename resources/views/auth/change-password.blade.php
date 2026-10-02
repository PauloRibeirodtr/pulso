@extends('adminlte::page')

@section('title', 'Alterar senha')

@section('content_header')
    <h1 class="m-0">Alterar minha senha</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success" role="alert">
                                Senha alterada com sucesso.
                            </div>
                        @endif

                        <form action="{{ route('user-password.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="current_password">Senha atual</label>
                                <input
                                    id="current_password"
                                    type="password"
                                    name="current_password"
                                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                    required
                                    autocomplete="current-password"
                                >

                                @error('current_password', 'updatePassword')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password">Nova senha</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                    required
                                    autocomplete="new-password"
                                >

                                @error('password', 'updatePassword')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Confirmar nova senha</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Salvar nova senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
