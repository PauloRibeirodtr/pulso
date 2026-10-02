@extends('adminlte::page')

@section('title', $participacao->aplicacao->nome)

@section('content_header')
    <div class="pulso-student-wrap">
        <a href="{{ route('estudante.dashboard') }}" class="d-inline-block mb-3">← Minhas pesquisas</a>
        <h1 class="mb-1">{{ $participacao->aplicacao->nome }}</h1>
        <p class="text-muted mb-0">Responda com sinceridade. Valores menores indicam uma experiência menos favorável.</p>
    </div>
@stop

@section('content')
    <div class="pulso-student-wrap">
        <x-flash-message />
        <x-form-errors />

        @if($participacao->status === App\StatusParticipacao::Finalizada)
            <div class="alert alert-success" role="status">Sua participação foi finalizada em {{ $participacao->finalizado_em?->format('d/m/Y \à\s H:i') }}. Obrigado por compartilhar sua experiência!</div>
        @elseif(! $podeResponder)
            <div class="alert alert-warning" role="status">Esta pesquisa não está disponível para respostas neste momento. As respostas já salvas permanecem registradas.</div>
        @endif

        <form method="POST" action="{{ route('estudante.pesquisas.save', $participacao) }}">
            @csrf

            @foreach($participacao->aplicacao->perguntas as $pergunta)
                <fieldset class="card pulso-student-card" @if(! $podeResponder) disabled @endif>
                    <div class="card-body">
                        <span class="pulso-eyebrow">{{ $pergunta->ordem }} de {{ $participacao->aplicacao->perguntas->count() }} · {{ ucfirst($pergunta->origem->value) }}</span>
                        <legend class="h5 mt-2 mb-3">{{ $pergunta->texto_snapshot }} @if($pergunta->obrigatoria)<span class="text-danger" aria-label="obrigatória">*</span>@endif</legend>
                        <div class="pulso-scale" role="group" aria-label="Escala de {{ $pergunta->valor_minimo_snapshot }} a {{ $pergunta->valor_maximo_snapshot }}">
                            @for($valor = $pergunta->valor_minimo_snapshot; $valor <= $pergunta->valor_maximo_snapshot; $valor++)
                                <label class="pulso-scale-option">
                                    <input type="radio" name="respostas[{{ $pergunta->id }}]" value="{{ $valor }}" @checked((string) old('respostas.'.$pergunta->id, $respostas->get($pergunta->id)) === (string) $valor)>
                                    <span>{{ $valor }}</span>
                                </label>
                            @endfor
                        </div>
                        <div class="d-flex justify-content-between text-muted pulso-scale-caption"><small>Menos favorável</small><small>Mais favorável</small></div>
                    </div>
                </fieldset>
            @endforeach

            <div class="card pulso-student-card"><div class="card-body">
                <h2 class="h5 mb-2">Antes de finalizar</h2>
                <div class="form-group">
                    <label for="comentario">Deseja deixar algum comentário? <span class="text-muted">(opcional)</span></label>
                    <textarea id="comentario" name="comentario" class="form-control" rows="4" maxlength="5000" @if(! $podeResponder) disabled @endif>{{ old('comentario', $participacao->comentarioGeral?->comentario ?? '') }}</textarea>
                    <small class="form-text text-muted">Há apenas este campo de comentário geral; você não precisa detalhar situações pessoais ou sensíveis.</small>
                </div>
                @if($podeResponder)
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="solicitar_conversa" name="solicitar_conversa" value="1" class="custom-control-input" @checked(old('solicitar_conversa'))>
                        <label for="solicitar_conversa" class="custom-control-label">Gostaria de conversar com a Coordenação ou equipe responsável</label>
                    </div>
                    <small class="form-text text-muted">O pedido será registrado somente quando você finalizar a pesquisa. Não é necessário explicar o motivo aqui.</small>
                @elseif($participacao->solicitacaoAcompanhamento)
                    <p class="mb-0">Sua solicitação de conversa está {{ $participacao->solicitacaoAcompanhamento->status->value === 'atendida' ? 'marcada como atendida' : 'pendente' }}.</p>
                @endif
            </div></div>

            @if($podeResponder)
                <div class="pulso-student-actions">
                    <button type="submit" class="btn btn-outline-primary">Salvar e continuar depois</button>
                    <button type="submit" formaction="{{ route('estudante.pesquisas.finish', $participacao) }}" class="btn btn-primary" onclick="return confirm('Finalizar a pesquisa? Depois do envio, as respostas não poderão ser alteradas.')">Finalizar pesquisa</button>
                </div>
                <p class="text-muted small mt-3">* Pergunta obrigatória. Você pode salvar o andamento mesmo sem responder tudo.</p>
            @endif
        </form>
    </div>
@stop
