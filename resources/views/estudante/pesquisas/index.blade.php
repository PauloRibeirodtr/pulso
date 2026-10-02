@extends('adminlte::page')

@section('title', 'Minhas pesquisas')

@section('content_header')
    <div class="pulso-student-wrap">
        <h1 class="mb-1">Minhas pesquisas</h1>
        <p class="text-muted mb-0">Olá, {{ auth()->user()->name }}. Sua participação ajuda a melhorar a experiência estudantil no IFMS.</p>
    </div>
@stop

@section('content')
    <div class="pulso-student-wrap">
        <x-flash-message />

        @forelse($participacoes as $participacao)
            @php
                $disponivel = $participacao->aplicacao->estaDisponivelParaResposta();
                $finalizada = $participacao->status === App\StatusParticipacao::Finalizada;
            @endphp
            <article class="card card-outline card-primary pulso-student-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <span class="pulso-eyebrow">{{ ucfirst($participacao->aplicacao->tipo_aplicacao->value) }}</span>
                            <h2 class="h5 mt-2 mb-2">{{ $participacao->aplicacao->nome }}</h2>
                        </div>
                        @if($finalizada)<span class="badge badge-success">Finalizada</span>
                        @elseif($disponivel)<span class="badge badge-success">Disponível</span>
                        @else<span class="badge badge-secondary">Indisponível</span>@endif
                    </div>

                    <p class="text-muted mb-2">
                        @if($participacao->aplicacao->data_encerramento)
                            Prazo até {{ $participacao->aplicacao->data_encerramento->format('d/m/Y \à\s H:i') }}.
                        @else
                            Sem prazo de encerramento definido.
                        @endif
                    </p>
                    <p class="mb-3">{{ $participacao->respostas_count }} resposta(s) salva(s).</p>

                    <a href="{{ route('estudante.pesquisas.show', $participacao) }}" class="btn {{ $disponivel && ! $finalizada ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $finalizada ? 'Ver participação' : ($disponivel ? 'Responder pesquisa' : 'Ver pesquisa') }}
                    </a>
                </div>
            </article>
        @empty
            <div class="card card-outline card-primary"><div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-2x text-primary mb-3" aria-hidden="true"></i>
                <h2 class="h5">Ainda não há pesquisas para você</h2>
                <p class="text-muted mb-0">Quando uma pesquisa for aberta para sua turma, ela aparecerá aqui.</p>
            </div></div>
        @endforelse

        {{ $participacoes->links('pagination::bootstrap-4') }}
    </div>
@stop
