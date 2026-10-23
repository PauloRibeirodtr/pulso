@props(['linhas', 'series', 'titulo'])
<div class="report-chart" role="img" aria-label="{{ $titulo }}">
    <h3 class="h6 font-weight-bold">{{ $titulo }}</h3>
    <p class="small">@foreach($series as $serie)<span class="mr-3"><span class="report-key" style="background:{{ $serie['cor'] }}"></span> {{ $serie['nome'] }}</span>@endforeach</p>
    @forelse($linhas as $linha)
        @php($total = collect($series)->sum(fn ($serie) => $linha[$serie['campo']]))
        <div class="mb-3">
            <div class="small mb-1">{{ $linha['grupo'] }}</div>
            <div class="report-stacked">
                @foreach($series as $serie)
                    @php($valor = $linha[$serie['campo']])
                    @if($valor > 0 && $total > 0)<span style="width:{{ round($valor * 100 / $total, 3) }}%;background:{{ $serie['cor'] }}" title="{{ $serie['nome'] }}: {{ $valor }}"></span>@endif
                @endforeach
            </div>
            <div class="small mt-1">@foreach($series as $serie){{ $serie['nome'] }}: <strong>{{ $linha[$serie['campo']] }}</strong>{{ $loop->last ? '' : ' · ' }}@endforeach</div>
        </div>
    @empty<p class="text-muted">Sem dados para exibir.</p>@endforelse
</div>
