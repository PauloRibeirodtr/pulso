@props(['action', 'method' => 'POST', 'campus' => null])

<form action="{{ $action }}" method="POST">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form-errors />

    <div class="form-group">
        <label for="nome">Nome</label>
        <input
            id="nome"
            type="text"
            name="nome"
            value="{{ old('nome', $campus?->nome) }}"
            class="form-control @error('nome') is-invalid @enderror"
            maxlength="150"
            required
            autofocus
        >
        @error('nome')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="sigla">Sigla ou código</label>
        <input
            id="sigla"
            type="text"
            name="sigla"
            value="{{ old('sigla', $campus?->sigla) }}"
            class="form-control @error('sigla') is-invalid @enderror"
            maxlength="30"
        >
        @error('sigla')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <input type="hidden" name="ativo" value="0">
    <div class="custom-control custom-switch mb-4">
        <input
            id="ativo"
            type="checkbox"
            name="ativo"
            value="1"
            class="custom-control-input"
            @checked((bool) old('ativo', $campus?->ativo ?? true))
        >
        <label class="custom-control-label" for="ativo">Campus ativo</label>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Salvar
    </button>
    <a href="{{ route('campi.index') }}" class="btn btn-default">Cancelar</a>
</form>
