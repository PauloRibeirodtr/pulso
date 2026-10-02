@extends('adminlte::page')

@section('title', 'Importar estudantes')

@section('content_header')
    <h1 class="m-0">Importar estudantes</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary"><div class="card-body">
            <p>Selecione a turma e envie um arquivo CSV em UTF-8 com as colunas <code>matricula;nome</code>. Exemplo:</p>
            <pre class="bg-light border rounded p-3">matricula;nome
20260001;Ana Silva
20260002;João Souza</pre>
            <p class="text-muted">Até 500 estudantes por arquivo. Matrículas repetidas ou já cadastradas impedem a importação inteira. A senha inicial de cada estudante será o nome amigável da turma.</p>

            <form action="{{ route('estudantes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-form-errors />
                <div class="form-group">
                    <label for="turma_id">Turma</label>
                    <select id="turma_id" name="turma_id" required class="form-control @error('turma_id') is-invalid @enderror">
                        <option value="">Selecione a turma</option>
                        @foreach ($turmas as $turma)
                            <option value="{{ $turma->id }}" @selected((string) old('turma_id') === (string) $turma->id)>
                                {{ $turma->curso->campus->nome }} / {{ $turma->curso->nome }} / {{ $turma->nome_amigavel }}
                            </option>
                        @endforeach
                    </select>
                    @error('turma_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="arquivo">Arquivo CSV</label>
                    <input id="arquivo" name="arquivo" type="file" accept=".csv,.txt,text/csv,text/plain" required class="form-control-file @error('arquivo') is-invalid @enderror">
                    @error('arquivo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-import mr-1"></i> Importar</button>
                <a href="{{ route('estudantes.index') }}" class="btn btn-default">Cancelar</a>
            </form>
        </div></div>
    </div>
@stop
