@props(['action', 'cursos', 'method' => 'POST', 'turma' => null])

<form action="{{ $action }}" method="POST">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form-errors />

    <div class="form-group">
        <label for="curso_id">Curso</label>
        <select
            id="curso_id"
            name="curso_id"
            class="form-control @error('curso_id') is-invalid @enderror"
            required
        >
            <option value="">Selecione</option>
            @foreach ($cursos as $curso)
                <option
                    value="{{ $curso->id }}"
                    @selected((string) old('curso_id', $turma?->curso_id) === (string) $curso->id)
                >
                    {{ $curso->campus->nome }} — {{ $curso->nome }}{{ $curso->sigla ? ' ('.$curso->sigla.')' : '' }}
                </option>
            @endforeach
        </select>
        @error('curso_id')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="nome_amigavel">Nome amigável</label>
        <input
            id="nome_amigavel"
            type="text"
            name="nome_amigavel"
            value="{{ old('nome_amigavel', $turma?->nome_amigavel) }}"
            class="form-control @error('nome_amigavel') is-invalid @enderror"
            maxlength="100"
            required
            autofocus
        >
        <small class="form-text text-muted">
            Este valor também será usado como senha inicial dos estudantes da turma.
        </small>
        @error('nome_amigavel')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="codigo_academico">Código acadêmico</label>
        <input
            id="codigo_academico"
            type="text"
            name="codigo_academico"
            value="{{ old('codigo_academico', $turma?->codigo_academico) }}"
            class="form-control @error('codigo_academico') is-invalid @enderror"
            maxlength="100"
        >
        @error('codigo_academico')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="ano_ingresso">Ano de ingresso</label>
                <input
                    id="ano_ingresso"
                    type="number"
                    name="ano_ingresso"
                    value="{{ old('ano_ingresso', $turma?->ano_ingresso) }}"
                    class="form-control @error('ano_ingresso') is-invalid @enderror"
                    min="1900"
                    max="{{ now()->year + 1 }}"
                >
                @error('ano_ingresso')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="periodo_ingresso">Período de ingresso</label>
                <input
                    id="periodo_ingresso"
                    type="text"
                    name="periodo_ingresso"
                    value="{{ old('periodo_ingresso', $turma?->periodo_ingresso) }}"
                    class="form-control @error('periodo_ingresso') is-invalid @enderror"
                    maxlength="30"
                    placeholder="Ex.: 1º semestre"
                >
                @error('periodo_ingresso')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <input type="hidden" name="ativo" value="0">
    <div class="custom-control custom-switch mb-4">
        <input
            id="ativo"
            type="checkbox"
            name="ativo"
            value="1"
            class="custom-control-input"
            @checked((bool) old('ativo', $turma?->ativo ?? true))
        >
        <label class="custom-control-label" for="ativo">Turma ativa</label>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Salvar
    </button>
    <a href="{{ route('turmas.index') }}" class="btn btn-default">Cancelar</a>
</form>
