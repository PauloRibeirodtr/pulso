@props(['action', 'method' => 'POST', 'servidor' => null, 'campi' => [], 'cursos' => []])

<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <x-form-errors />

    <div class="form-group">
        <label for="siape">SIAPE</label>
        <input id="siape" name="siape" type="text" inputmode="numeric" maxlength="50" required autofocus
            value="{{ old('siape', $servidor?->identificador_institucional) }}"
            class="form-control @error('siape') is-invalid @enderror">
        @error('siape') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" maxlength="255" required
            value="{{ old('nome', $servidor?->name) }}" class="form-control @error('nome') is-invalid @enderror">
        @error('nome') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="email">E-mail institucional</label>
        <input id="email" name="email" type="email" maxlength="255" autocomplete="email"
            value="{{ old('email', $servidor?->email) }}" class="form-control @error('email') is-invalid @enderror">
        <small class="form-text text-muted">O e-mail permite recuperação de senha. O acesso ao sistema usa o SIAPE.</small>
        @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="password">{{ $servidor ? 'Nova senha (opcional)' : 'Senha inicial' }}</label>
        <input id="password" name="password" type="password" autocomplete="new-password"
            class="form-control @error('password') is-invalid @enderror" @required($servidor === null)>
        @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation">Confirmar senha</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
            class="form-control" @required($servidor === null)>
    </div>

    <input type="hidden" name="ativo" value="0">
    <div class="custom-control custom-switch mb-4">
        <input id="ativo" name="ativo" type="checkbox" value="1" class="custom-control-input"
            @checked((bool) old('ativo', $servidor?->ativo ?? true))>
        <label class="custom-control-label" for="ativo">Conta ativa</label>
    </div>

    @if ($servidor === null)
        <h2 class="h5 mt-4">Primeira permissão</h2>
        <p class="text-muted">O servidor precisa de uma permissão ativa para entrar no sistema.</p>
        <x-servidores.permissao-fields :$campi :$cursos />
    @endif

    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar</button>
    <a href="{{ route('servidores.index') }}" class="btn btn-default">Cancelar</a>
</form>
