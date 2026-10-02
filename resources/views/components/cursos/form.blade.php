@props(['action', 'campi', 'method' => 'POST', 'curso' => null])

<form action="{{ $action }}" method="POST">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form-errors />

    <div class="form-group">
        <label for="campus_id">Campus</label>
        <select
            id="campus_id"
            name="campus_id"
            class="form-control @error('campus_id') is-invalid @enderror"
            required
        >
            <option value="">Selecione</option>
            @foreach ($campi as $campus)
                <option
                    value="{{ $campus->id }}"
                    @selected((string) old('campus_id', $curso?->campus_id) === (string) $campus->id)
                >
                    {{ $campus->nome }}{{ $campus->sigla ? ' — '.$campus->sigla : '' }}
                </option>
            @endforeach
        </select>
        @error('campus_id')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="nome">Nome</label>
        <input
            id="nome"
            type="text"
            name="nome"
            value="{{ old('nome', $curso?->nome) }}"
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
            value="{{ old('sigla', $curso?->sigla) }}"
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
            @checked((bool) old('ativo', $curso?->ativo ?? true))
        >
        <label class="custom-control-label" for="ativo">Curso ativo</label>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Salvar
    </button>
    <a href="{{ route('cursos.index') }}" class="btn btn-default">Cancelar</a>
</form>
