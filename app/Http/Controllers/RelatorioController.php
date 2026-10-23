<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrarRelatoriosRequest;
use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Turma;
use App\Services\Auditoria;
use App\Services\RelatoriosPesquisa;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function index(FiltrarRelatoriosRequest $request, RelatoriosPesquisa $relatorios): View
    {
        $filtros = $request->validated();
        $opcoes = $relatorios->filtrar(AplicacaoPesquisa::query()
            ->whereIn('turma_id', Turma::query()->visiveisPara($request->user())->select('id')), $filtros)
            ->with('turma.curso.campus')->orderBy('data_abertura')->orderBy('id')->get();
        $selecionadas = $this->selecionar($opcoes, $filtros);
        $relatorio = $filtros['relatorio'] ?? 'resultado';
        $modo = $selecionadas->count() === 1 ? 'individual' : ($filtros['modo'] ?? 'comparativo');
        $dados = $request->boolean('gerar') ? $relatorios->gerar($selecionadas, $modo, $relatorio) : null;

        return view('relatorios.index', [
            'filtros' => $filtros, 'opcoes' => $opcoes, 'selecionadas' => $selecionadas,
            'relatorio' => $relatorio, 'modo' => $modo, 'dados' => $dados,
            'titulos' => RelatoriosPesquisa::TITULOS, 'geradoEm' => now(),
            'campi' => Campus::query()->visiveisPara($request->user())->orderBy('nome')->get(),
            'cursos' => Curso::query()->visiveisPara($request->user())->orderBy('nome')->get(),
            'turmas' => Turma::query()->visiveisPara($request->user())->with('curso')->orderBy('nome_amigavel')->get(),
        ]);
    }

    public function export(FiltrarRelatoriosRequest $request, RelatoriosPesquisa $relatorios, Auditoria $auditoria): StreamedResponse
    {
        $filtros = $request->validated();
        $opcoes = $relatorios->filtrar(AplicacaoPesquisa::query()
            ->whereIn('turma_id', Turma::query()->visiveisPara($request->user())->select('id'))
            ->whereKey($filtros['aplicacoes'] ?? []), $filtros)
            ->with('turma.curso.campus')->orderBy('data_abertura')->orderBy('id')->get();
        $selecionadas = $this->selecionar($opcoes, $filtros);
        if ($selecionadas->isEmpty()) {
            throw ValidationException::withMessages(['aplicacoes' => 'Selecione ao menos uma aplicação para exportar.']);
        }
        $relatorio = $filtros['relatorio'] ?? 'resultado';
        $modo = $selecionadas->count() === 1 ? 'individual' : ($filtros['modo'] ?? 'comparativo');
        $dados = $relatorios->gerar($selecionadas, $modo, $relatorio);
        foreach ($selecionadas as $aplicacao) {
            $auditoria->registrar($request->user(), 'relatorios.exportados', $aplicacao);
        }

        return response()->streamDownload(function () use ($relatorio, $modo, $dados, $selecionadas): void {
            $saida = fopen('php://output', 'w');
            if ($saida === false) {
                throw new \RuntimeException('Não foi possível gerar o relatório.');
            }
            fwrite($saida, "\xEF\xBB\xBF");
            $escrever = function (array $valores) use ($saida): void {
                fputcsv($saida, array_map(fn ($valor) => is_string($valor) && preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $valor) ? "'".$valor : $valor, $valores), ';', '"', '');
            };
            $escrever(['Relatório', RelatoriosPesquisa::TITULOS[$relatorio], 'Modo', $modo, 'Gerado em', now()->toIso8601String()]);
            foreach ($selecionadas as $aplicacao) {
                $escrever(['Aplicação selecionada', $aplicacao->id, $aplicacao->nome, $aplicacao->turma->curso->nome, $aplicacao->turma->nome_amigavel, $aplicacao->data_abertura?->toIso8601String(), $aplicacao->status->value]);
            }
            foreach ($dados['avisos'] as $aviso) {
                $escrever(['Observação', $aviso]);
            }
            $escrever(['Estudantes únicos convidados', $dados['resumo']['estudantes_unicos'], 'Participações previstas', $dados['resumo']['participacoes'], 'Respondentes únicos', $dados['resumo']['respondentes_unicos']]);
            if ($relatorio === 'participacao') {
                $escrever(['Aplicação / grupo', 'Previstas', 'Finalizadas', 'Em andamento', 'Não iniciadas', 'Conclusão (%)']);
                foreach ($dados['participacao'] as $linha) {
                    $escrever(array_values($linha));
                }
            } elseif ($relatorio === 'solicitacoes') {
                $escrever(['Aplicação / grupo', 'Pendentes', 'Atendidas', 'Espera até 7 dias', 'Espera de 8 a 14 dias', 'Espera de 15 dias ou mais', 'Espera média pendente (dias)', 'Tempo médio até atendimento (dias)']);
                foreach ($dados['solicitacoes'] as $linha) {
                    $escrever(array_values($linha));
                }
            } else {
                $escrever(['Seção', 'Aplicação / grupo', 'Bloco', 'Dimensão / pergunta', 'Mínimo', 'Máximo', 'Estudantes distintos', 'Respostas válidas', 'Média', 'Distribuição (nota: quantidade)']);
                foreach (['dimensoes' => 'Dimensão', 'perguntas' => 'Pergunta'] as $chave => $secao) {
                    foreach ($dados[$chave] as $linha) {
                        $escrever([$secao, $linha['grupo'], $linha['origem'], $linha['dimensao'] ?? $linha['texto'], $linha['minimo'], $linha['maximo'], $linha['estudantes'], $linha['respostas'], $linha['media'] === null ? 'Restrita' : number_format($linha['media'], 2, ',', ''), $chave === 'perguntas' && $linha['distribuicao'] !== null ? json_encode($linha['distribuicao'], JSON_THROW_ON_ERROR) : '']);
                    }
                }
            }
            fclose($saida);
        }, 'relatorio-'.$relatorio.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    /**
     * @param  Collection<int, AplicacaoPesquisa>  $opcoes
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, AplicacaoPesquisa>
     */
    private function selecionar(Collection $opcoes, array $filtros): Collection
    {
        $ids = array_map('intval', $filtros['aplicacoes'] ?? []);
        $selecionadas = $opcoes->whereIn('id', $ids)->values();
        if ($selecionadas->count() !== count($ids)) {
            throw ValidationException::withMessages(['aplicacoes' => 'Alguma aplicação selecionada não corresponde aos filtros. Ajuste os filtros ou a seleção.']);
        }

        return $selecionadas;
    }
}
