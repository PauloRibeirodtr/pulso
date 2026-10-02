@props(['action', 'turmas', 'method' => 'POST', 'estudante' => null])

<form action="{{ $action }}" method="POST">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <x-form-errors />

    <div class="form-group">
        <label for="matricula">Matrícula</label>
        <input id="matricula" name="matricula" type="text" maxlength="50" required autofocus
            value="{{ old('matricula', $estudante?->usuario?->identificador_institucional) }}"
            class="form-control @error('matricula') is-invalid @enderror">
        @error('matricula') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="nome">Nome completo</label>
        <input id="nome" name="nome" type="text" maxlength="255" required
            value="{{ old('nome', $estudante?->usuario?->name) }}"
            class="form-control @error('nome') is-invalid @enderror">
        @error('nome') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="turma_id">Turma</label>
        <select id="turma_id" name="turma_id" required class="form-control @error('turma_id') is-invalid @enderror">
            <option value="">Selecione a turma</option>
            @foreach ($turmas as $turma)
                <option value="{{ $turma->id }}"
                    @selected((string) old('turma_id', $estudante?->turma_id) === (string) $turma->id)>
                    {{ $turma->curso->campus->nome }} / {{ $turma->curso->nome }} / {{ $turma->nome_amigavel }}
                </option>
            @endforeach
        </select>
        @error('turma_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
        @if ($estudante === null)
            <small class="form-text text-muted">A senha inicial será o nome amigável da turma selecionada.</small>
        @else
            <small class="form-text text-muted">Alterar a turma não redefine a senha. Use a ação “Redefinir senha” quando necessário.</small>
        @endif
    </div>

    <div class="form-group">
        <label for="situacao_academica">Situação</label>
        <select id="situacao_academica" name="situacao_academica" required class="form-control @error('situacao_academica') is-invalid @enderror">
            <option value="ativo" @selected(old('situacao_academica', $estudante?->situacao_academica ?? 'ativo') === 'ativo')>Ativo</option>
            <option value="inativo" @selected(old('situacao_academica', $estudante?->situacao_academica) === 'inativo')>Inativo</option>
        </select>
        @error('situacao_academica') <span class="invalid-feedback">{{ $message }}</span> @enderror
        <small class="form-text text-muted">Estudantes inativos não poderão acessar o sistema.</small>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar</button>
    <a href="{{ route('estudantes.index') }}" class="btn btn-default">Cancelar</a>
</form>
