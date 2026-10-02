@extends('adminlte::page')
@section('title', $modeloQuestionario->nome)
@section('content_header')<div class="d-flex justify-content-between align-items-center"><h1>{{ $modeloQuestionario->nome }}</h1><a class="btn btn-outline-secondary" href="{{ route('modelos-questionario.index') }}">Voltar</a></div>@stop
@section('content')
    <x-flash-message /><x-form-errors />
    <div class="card card-outline card-primary"><div class="card-body">
        <p><strong>Bloco:</strong> {{ $modeloQuestionario->curso?->nome ?? 'Institucional' }} · <strong>Tipo:</strong> {{ ucfirst($modeloQuestionario->tipo->value) }} · <strong>Estado:</strong> {{ $modeloQuestionario->publicado_em ? 'Publicado e congelado' : 'Rascunho' }}</p>
        @if($modeloQuestionario->descricao)<p>{{ $modeloQuestionario->descricao }}</p>@endif
        @can('update', $modeloQuestionario)<a class="btn btn-outline-primary" href="{{ route('modelos-questionario.edit', $modeloQuestionario) }}">Editar dados</a>
            <form class="d-inline" method="POST" action="{{ route('modelos-questionario.publish', $modeloQuestionario) }}">@csrf <button class="btn btn-success" onclick="return confirm('Publicar e congelar este modelo?')">Publicar e congelar</button></form>
        @endcan
        @can('duplicate', $modeloQuestionario)<form class="form-inline mt-3" method="POST" action="{{ route('modelos-questionario.duplicate', $modeloQuestionario) }}">@csrf <label for="nome_copia" class="mr-2">Criar cópia em rascunho:</label><input id="nome_copia" name="nome" class="form-control mr-2" required maxlength="150" placeholder="Novo nome"><button class="btn btn-outline-primary">Duplicar</button></form>@endcan
        @can('changeStatus', $modeloQuestionario)<form class="mt-3" method="POST" action="{{ route('modelos-questionario.status', $modeloQuestionario) }}">@csrf @method('PATCH')<input type="hidden" name="ativo" value="{{ $modeloQuestionario->ativo ? 0 : 1 }}"><button class="btn btn-outline-secondary">{{ $modeloQuestionario->ativo ? 'Retirar de uso' : 'Reativar modelo' }}</button></form>@endcan
    </div></div>
    <div class="card"><div class="card-header"><strong>Pré-visualização das perguntas</strong></div><div class="card-body">
        @forelse($modeloQuestionario->itens as $item)<div class="border-bottom py-3"><div class="d-flex justify-content-between"><div><strong>{{ $item->ordem }}.</strong> {{ $item->pergunta->texto }} @if($item->obrigatoria)<span class="badge badge-info">Obrigatória</span>@endif @if($item->pergunta->sensivel)<span class="badge badge-warning">Sensível</span>@endif<br><small class="text-muted">{{ $item->pergunta->dimensao->nome }} · Escala de 1 a 5</small></div>
            @can('update', $modeloQuestionario)<div class="btn-group btn-group-sm"><form method="POST" action="{{ route('modelos-questionario.perguntas.move', [$modeloQuestionario, $item, 'subir']) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary" aria-label="Subir">↑</button></form><form method="POST" action="{{ route('modelos-questionario.perguntas.move', [$modeloQuestionario, $item, 'descer']) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary" aria-label="Descer">↓</button></form><form method="POST" action="{{ route('modelos-questionario.perguntas.destroy', [$modeloQuestionario, $item]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">Remover</button></form></div>@endcan
        </div></div>@empty<p class="text-muted">Ainda não há perguntas neste modelo.</p>@endforelse
    </div></div>
    @can('update', $modeloQuestionario)<div class="card"><div class="card-header"><strong>Adicionar pergunta</strong></div><div class="card-body"><form method="POST" action="{{ route('modelos-questionario.perguntas.store', $modeloQuestionario) }}">@csrf
        <div class="form-group"><label for="pergunta_id">Pergunta do bloco {{ $modeloQuestionario->curso?->sigla ?? 'institucional' }}</label><select id="pergunta_id" name="pergunta_id" class="form-control" required><option value="">Selecione</option>@foreach($perguntasDisponiveis as $pergunta)<option value="{{ $pergunta->id }}">{{ $pergunta->texto }} ({{ $pergunta->dimensao->nome }})</option>@endforeach</select></div>
        <div class="form-group"><label for="obrigatoria">Obrigatoriedade</label><select id="obrigatoria" name="obrigatoria" class="form-control"><option value="1">Obrigatória</option><option value="0">Opcional</option></select></div><button class="btn btn-primary">Adicionar</button>
    </form></div></div>@endcan
@stop
