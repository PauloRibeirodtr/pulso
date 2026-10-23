@props(['pontos', 'titulo', 'minimo', 'maximo'])
@php
    $amplitude = max(1, $maximo - $minimo);
    $quantidade = count($pontos);
    $coordenadas = collect($pontos)->values()->map(fn ($ponto, $indice) => [
        'x' => $quantidade > 1 ? 55 + $indice * 610 / ($quantidade - 1) : 360,
        'y' => $ponto['media'] === null ? null : 170 - ($ponto['media'] - $minimo) * 140 / $amplitude,
        'ponto' => $ponto,
    ]);
@endphp
<div class="report-chart mb-4">
    <h3 class="h6 font-weight-bold">{{ $titulo }} · escala {{ $minimo }} a {{ $maximo }}</h3>
    <svg viewBox="0 0 720 210" class="report-line" role="img" aria-label="{{ $titulo }}. Aplicações em ordem cronológica; consulte os valores na tabela.">
        <title>{{ $titulo }}</title>
        @foreach([$minimo, ($minimo + $maximo) / 2, $maximo] as $valor)
            @php($y = 170 - ($valor - $minimo) * 140 / $amplitude)
            <line x1="55" y1="{{ $y }}" x2="665" y2="{{ $y }}" stroke="#cbd5e1" />
            <text x="42" y="{{ $y + 4 }}" text-anchor="end" font-size="12" fill="#334155">{{ $valor }}</text>
        @endforeach
        @foreach($coordenadas as $indice => $coordenada)
            @if($indice > 0 && $coordenada['y'] !== null && $coordenadas[$indice - 1]['y'] !== null)
                <line x1="{{ $coordenadas[$indice - 1]['x'] }}" y1="{{ $coordenadas[$indice - 1]['y'] }}" x2="{{ $coordenada['x'] }}" y2="{{ $coordenada['y'] }}" stroke="#087e8b" stroke-width="3" />
            @endif
            @if($coordenada['y'] !== null)
                <circle cx="{{ $coordenada['x'] }}" cy="{{ $coordenada['y'] }}" r="5" fill="#087e8b"><title>{{ $coordenada['ponto']['grupo'] }}: {{ number_format($coordenada['ponto']['media'], 2, ',', '.') }}</title></circle>
                <text x="{{ $coordenada['x'] }}" y="{{ $coordenada['y'] - 11 }}" text-anchor="middle" font-size="12" fill="#134e4a">{{ number_format($coordenada['ponto']['media'], 2, ',', '.') }}</text>
            @else
                <text x="{{ $coordenada['x'] }}" y="190" text-anchor="middle" font-size="10" fill="#64748b">Restrita / sem dados</text>
            @endif
            <text x="{{ $coordenada['x'] }}" y="207" text-anchor="middle" font-size="12" fill="#334155">{{ $indice + 1 }}</text>
        @endforeach
    </svg>
    <ol class="small mb-0">@foreach($pontos as $ponto)<li>{{ $ponto['grupo'] }} — {{ $ponto['media'] === null ? 'Restrita / sem dados' : number_format($ponto['media'], 2, ',', '.') }}</li>@endforeach</ol>
</div>
