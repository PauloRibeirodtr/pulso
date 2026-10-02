<?php

namespace App\Actions;

use App\Models\Turma;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportarEstudantes
{
    public function __construct(private CadastrarEstudante $cadastrarEstudante) {}

    public function handle(UploadedFile $arquivo, Turma $turma): int
    {
        $linhas = $this->lerArquivo($arquivo);
        $matriculas = array_column($linhas, 'matricula');

        $existentes = User::query()
            ->whereIn('identificador_institucional', $matriculas)
            ->pluck('identificador_institucional')
            ->all();

        if ($existentes !== []) {
            throw ValidationException::withMessages([
                'arquivo' => 'Estas matrículas já estão cadastradas: '.implode(', ', array_slice($existentes, 0, 10)).'.',
            ]);
        }

        DB::transaction(function () use ($linhas, $turma): void {
            foreach ($linhas as $linha) {
                $this->cadastrarEstudante->handle($linha, $turma);
            }
        });

        return count($linhas);
    }

    /**
     * @return list<array{matricula: string, nome: string}>
     */
    private function lerArquivo(UploadedFile $arquivo): array
    {
        $handle = fopen($arquivo->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['arquivo' => 'Não foi possível ler o arquivo enviado.']);
        }

        try {
            $primeiraLinha = fgets($handle);

            if ($primeiraLinha === false) {
                throw ValidationException::withMessages(['arquivo' => 'O arquivo está vazio.']);
            }

            $delimitador = substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',') ? ';' : ',';
            rewind($handle);

            $cabecalho = fgetcsv($handle, 0, $delimitador, '"', '');
            $colunas = array_map(
                fn (?string $valor): string => Str::lower(Str::ascii(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $valor)))),
                $cabecalho === false ? [] : $cabecalho,
            );

            if (count($colunas) !== 2 || $colunas[0] !== 'matricula' || $colunas[1] !== 'nome') {
                throw ValidationException::withMessages([
                    'arquivo' => 'O cabeçalho deve conter exatamente duas colunas: matricula;nome.',
                ]);
            }

            $linhas = [];
            $matriculas = [];
            $numeroLinha = 1;

            while (($campos = fgetcsv($handle, 0, $delimitador, '"', '')) !== false) {
                $numeroLinha++;

                if (count($campos) === 1 && trim((string) $campos[0]) === '') {
                    continue;
                }

                if (count($campos) !== 2 || ! mb_check_encoding(implode('', $campos), 'UTF-8')) {
                    throw ValidationException::withMessages([
                        'arquivo' => "A linha {$numeroLinha} deve ter matrícula e nome em UTF-8.",
                    ]);
                }

                $linha = [
                    'matricula' => Str::squish((string) $campos[0]),
                    'nome' => Str::squish((string) $campos[1]),
                ];

                $validacao = Validator::make($linha, [
                    'matricula' => ['required', 'string', 'max:50'],
                    'nome' => ['required', 'string', 'max:255'],
                ]);

                if ($validacao->fails()) {
                    throw ValidationException::withMessages([
                        'arquivo' => "A linha {$numeroLinha} possui matrícula ou nome inválido.",
                    ]);
                }

                if (isset($matriculas[$linha['matricula']])) {
                    throw ValidationException::withMessages([
                        'arquivo' => "A matrícula da linha {$numeroLinha} está repetida no arquivo.",
                    ]);
                }

                $matriculas[$linha['matricula']] = true;
                $linhas[] = $linha;

                if (count($linhas) > 500) {
                    throw ValidationException::withMessages([
                        'arquivo' => 'O arquivo deve conter no máximo 500 estudantes por importação.',
                    ]);
                }
            }

            if ($linhas === []) {
                throw ValidationException::withMessages(['arquivo' => 'O arquivo não contém estudantes.']);
            }

            return $linhas;
        } finally {
            fclose($handle);
        }
    }
}
