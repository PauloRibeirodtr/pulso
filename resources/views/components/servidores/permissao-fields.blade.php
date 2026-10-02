@props(['campi', 'cursos', 'permissao' => null])

<div class="form-group">
    <label for="nivel_escopo">Nível de acesso</label>
    <select id="nivel_escopo" name="nivel_escopo" required class="form-control @error('nivel_escopo') is-invalid @enderror">
        <option value="">Selecione</option>
        <option value="instituicao" @selected(old('nivel_escopo', $permissao?->nivel_escopo?->value) === 'instituicao')>Instituição — todos os campi e cursos</option>
        <option value="campus" @selected(old('nivel_escopo', $permissao?->nivel_escopo?->value) === 'campus')>Campus — todos os cursos do campus</option>
        <option value="curso" @selected(old('nivel_escopo', $permissao?->nivel_escopo?->value) === 'curso')>Curso — todas as turmas do curso</option>
    </select>
    @error('nivel_escopo') <span class="invalid-feedback">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label for="campus_id">Campus</label>
    <select id="campus_id" name="campus_id" class="form-control @error('campus_id') is-invalid @enderror">
        <option value="">Selecione apenas para acesso por campus</option>
        @foreach ($campi as $campus)
            <option value="{{ $campus->id }}" @selected((string) old('campus_id', $permissao?->campus_id) === (string) $campus->id)>
                {{ $campus->nome }}{{ $campus->sigla ? ' ('.$campus->sigla.')' : '' }}
            </option>
        @endforeach
    </select>
    @error('campus_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label for="curso_id">Curso</label>
    <select id="curso_id" name="curso_id" class="form-control @error('curso_id') is-invalid @enderror">
        <option value="">Selecione apenas para acesso por curso</option>
        @foreach ($cursos as $curso)
            <option value="{{ $curso->id }}" @selected((string) old('curso_id', $permissao?->curso_id) === (string) $curso->id)>
                {{ $curso->campus->sigla ?? $curso->campus->nome }} / {{ $curso->nome }}{{ $curso->sigla ? ' ('.$curso->sigla.')' : '' }}
            </option>
        @endforeach
    </select>
    @error('curso_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label for="papel">Função do servidor</label>
    <input id="papel" name="papel" type="text" maxlength="50" required value="{{ old('papel', $permissao?->papel) }}"
        class="form-control @error('papel') is-invalid @enderror" placeholder="Ex.: coordenador, técnico, direção de ensino">
    <small class="form-text text-muted">A função é informativa; o nível de acesso acima define a autorização.</small>
    @error('papel') <span class="invalid-feedback">{{ $message }}</span> @enderror
</div>
