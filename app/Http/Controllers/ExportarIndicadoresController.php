<?php

namespace App\Http\Controllers;

use App\Models\AplicacaoPesquisa;
use App\Services\Auditoria;
use App\Services\IndicadoresPesquisa;
use App\StatusAplicacao;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarIndicadoresController extends Controller
{
    public function __invoke(AplicacaoPesquisa $aplicacao, IndicadoresPesquisa $indicadores, Auditoria $auditoria): StreamedResponse
    {
        Gate::authorize('view', $aplicacao);
        abort_if($aplicacao->status === StatusAplicacao::Planejada, 404);

        $aplicacao->load('turma.curso');
        $dimensoes = $indicadores->mediasPorDimensao($aplicacao);
        $auditoria->registrar(auth()->user(), 'indicadores.exportados', $aplicacao);

        return response()->streamDownload(function () use ($aplicacao, $dimensoes, $indicadores): void {
            $saida = fopen('php://output', 'w');
            if ($saida === false) {
                throw new \RuntimeException('Não foi possível gerar o arquivo CSV.');
            }

            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, ['Aplicação', 'Curso', 'Turma', 'Bloco', 'Dimensão', 'Estudantes', 'Respostas', 'Média'], ';', '"', '');

            foreach ($dimensoes as $dimensao) {
                $media = $indicadores->mediaPublicavel($dimensao);
                fputcsv($saida, [
                    $this->celulaSegura($aplicacao->nome),
                    $this->celulaSegura($aplicacao->turma->curso->sigla ?: $aplicacao->turma->curso->nome),
                    $this->celulaSegura($aplicacao->turma->nome_amigavel),
                    $dimensao->origem,
                    $this->celulaSegura($dimensao->dimensao),
                    $dimensao->estudantes,
                    $dimensao->respostas,
                    $media === null ? '' : number_format($media, 2, ',', ''),
                ], ';', '"', '');
            }

            fclose($saida);
        }, 'indicadores-aplicacao-'.$aplicacao->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function celulaSegura(string $valor): string
    {
        return preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $valor) ? "'".$valor : $valor;
    }
}
