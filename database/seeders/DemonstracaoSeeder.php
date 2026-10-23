<?php

namespace Database\Seeders;

use App\Actions\CadastrarEstudante;
use App\Actions\ComporAplicacaoPesquisa;
use App\EscopoModeloQuestionario;
use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\ModeloQuestionario;
use App\Models\Pergunta;
use App\Models\Turma;
use App\Models\User;
use App\NivelEscopo;
use App\StatusAplicacao;
use App\StatusParticipacao;
use App\StatusSolicitacaoAcompanhamento;
use App\TipoAplicacao;
use App\TipoUsuario;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemonstracaoSeeder extends Seeder
{
    public function run(): void
    {
        $campusExistente = Campus::query()->where('sigla', 'DEMO-PULSO')->first();
        if ($campusExistente) {
            AplicacaoPesquisa::query()
                ->whereHas('turma.curso', fn ($query) => $query->where('campus_id', $campusExistente->id))
                ->where('nome', 'like', '[DEMO] Rodada 4 — DEMO-%')
                ->update(['data_encerramento' => $this->encerramentoDemonstracao()]);
            $this->command?->warn('A base DEMO-PULSO já existe. Prazo da quarta rodada ajustado para 07/11/2026, às 23:59:59 (São Paulo). Demais dados e senhas preservados.');

            return;
        }

        $senha = Str::password(16);
        $referencia = CarbonImmutable::now();

        DB::transaction(function () use ($senha, $referencia): void {
            $campus = Campus::query()->create(['nome' => 'Campus Demonstração — dados fictícios', 'sigla' => 'DEMO-PULSO']);
            $gestor = $this->servidor('demo.gestao', 'Gestão institucional', $senha, NivelEscopo::Instituicao);
            $this->servidor('demo.campus', 'Direção do campus', $senha, NivelEscopo::Campus, $campus->id);

            $institucional = $this->modelo('Institucional', null, $gestor, $referencia, [
                ['Aprendizagem', 'Consigo acompanhar os conteúdos das disciplinas.'],
                ['Permanência e intenção de continuidade', 'Pretendo continuar meus estudos nesta instituição.'],
                ['Infraestrutura', 'Os espaços e equipamentos atendem às minhas necessidades de estudo.'],
                ['Apoio ao estudante', 'Sei onde buscar apoio quando preciso.'],
                ['Pertencimento e integração', 'Sinto que faço parte da comunidade do campus.'],
                ['Organização acadêmica', 'A organização das atividades favorece minha rotina de estudos.'],
            ]);

            foreach (['INFO' => 'Técnico em Informática', 'ADS' => 'Tecnologia em Análise e Desenvolvimento de Sistemas'] as $sigla => $nome) {
                $curso = $campus->cursos()->create(['nome' => $nome, 'sigla' => 'DEMO-'.$sigla]);
                $coordenador = $this->servidor('demo.'.strtolower($sigla), 'Coordenação '.$sigla, $senha, NivelEscopo::Curso, $curso->id);
                $modelo = $this->modelo($sigla, $curso, $coordenador, $referencia, $sigla === 'INFO' ? [
                    ['Aprendizagem', 'Compreendo os fundamentos de programação trabalhados no curso.'],
                    ['Infraestrutura', 'Tenho acesso aos recursos necessários para as aulas práticas de informática.'],
                    ['Aprendizagem', 'As atividades práticas ajudam a compreender os conteúdos.'],
                    ['Ambiente e experiência no curso', 'Percebo integração entre as disciplinas do curso técnico.'],
                ] : [
                    ['Aprendizagem', 'Consigo aplicar os conteúdos nos projetos de desenvolvimento de sistemas.'],
                    ['Aprendizagem', 'As atividades articulam teoria e prática.'],
                    ['Organização acadêmica', 'A distribuição das entregas dos projetos favorece meu aprendizado.'],
                    ['Ambiente e experiência no curso', 'O curso contribui para minha formação profissional.'],
                ]);
                $temporarias = [];
                foreach ([
                    'As orientações sobre as avaliações deste período foram claras.',
                    'Os horários de apoio deste período atendem às minhas necessidades.',
                ] as $texto) {
                    $temporarias[] = $this->pergunta('Organização acadêmica', $texto, $curso)->id;
                }

                foreach ([2025, 2026] as $indice => $ano) {
                    $turma = $curso->turmas()->create([
                        'nome_amigavel' => 'DEMO-'.$sigla.'-'.$ano,
                        'codigo_academico' => 'DEMO-'.$sigla.'-'.$ano,
                        'ano_ingresso' => $ano,
                        'periodo_ingresso' => '1',
                    ]);
                    for ($numero = 1; $numero <= 20; $numero++) {
                        app(CadastrarEstudante::class)->handle([
                            'matricula' => sprintf('DEMO-%s-%d-%02d', $sigla, $ano, $numero),
                            'nome' => $this->nomeEstudante($numero, $sigla, $ano),
                        ], $turma);
                    }

                    for ($rodada = 1; $rodada <= 4; $rodada++) {
                        $this->aplicacao($turma, $institucional, $modelo, $temporarias, $coordenador, $referencia, $rodada, $sigla === 'INFO' ? $indice : $indice + 2);
                    }
                }
            }
        });

        $this->command?->info('Demonstração criada: 2 cursos, 4 turmas, 80 estudantes e 16 aplicações (12 encerradas e 4 abertas até 07/11/2026, às 23:59:59, horário de São Paulo).');
        $this->command?->info('Servidores: demo.gestao, demo.campus, demo.info e demo.ads. Senha inicial: '.$senha);
        $this->command?->info('Estudante para responder ao vivo: DEMO-INFO-2026-01. Senha: DEMO-INFO-2026.');
        $this->command?->info('Matrículas: DEMO-{INFO|ADS}-{2025|2026}-{01..20}. Senha inicial: nome amigável da turma.');
    }

    private function encerramentoDemonstracao(): CarbonImmutable
    {
        return CarbonImmutable::create(2026, 11, 7, 23, 59, 59, 'America/Sao_Paulo')
            ->setTimezone(config('app.timezone'));
    }

    private function servidor(string $identificador, string $nome, string $senha, NivelEscopo $escopo, ?int $escopoId = null): User
    {
        if (User::query()->where('identificador_institucional', $identificador)->exists()) {
            throw new RuntimeException('Identificador já utilizado: '.$identificador.'. Nenhuma conta existente será alterada.');
        }

        $usuario = User::query()->create([
            'identificador_institucional' => $identificador,
            'name' => '[DEMO] '.$nome,
            'password' => $senha,
            'tipo_usuario' => TipoUsuario::Servidor,
        ]);
        $usuario->permissoes()->create([
            'nivel_escopo' => $escopo,
            'campus_id' => $escopo === NivelEscopo::Campus ? $escopoId : null,
            'curso_id' => $escopo === NivelEscopo::Curso ? $escopoId : null,
            'papel' => match ($escopo) {
                NivelEscopo::Instituicao => 'administrador_institucional',
                NivelEscopo::Campus => 'direcao',
                NivelEscopo::Curso => 'coordenador',
            },
        ]);

        return $usuario;
    }

    private function pergunta(string $dimensao, string $texto, ?Curso $curso): Pergunta
    {
        return Pergunta::query()->create([
            'dimensao_id' => Dimensao::query()->firstOrCreate(['nome' => $dimensao])->id,
            'curso_origem_id' => $curso?->id,
            'texto' => $texto,
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $perguntas
     */
    private function modelo(string $nome, ?Curso $curso, User $autor, CarbonImmutable $referencia, array $perguntas): ModeloQuestionario
    {
        $modelo = ModeloQuestionario::query()->create([
            'nome' => '[DEMO] '.$nome.' — versão 1',
            'curso_id' => $curso?->id,
            'escopo_modelo' => $curso ? EscopoModeloQuestionario::Curso : EscopoModeloQuestionario::Institucional,
            'tipo' => TipoAplicacao::Monitoramento,
            'descricao' => 'Questionário fictício para demonstração do Pulso IFMS.',
            'criado_por_usuario_id' => $autor->id,
            'created_at' => $referencia->subDays(150),
        ]);
        foreach ($perguntas as $indice => [$dimensao, $texto]) {
            $modelo->itens()->create([
                'pergunta_id' => $this->pergunta($dimensao, $texto, $curso)->id,
                'ordem' => $indice + 1,
                'obrigatoria' => true,
            ]);
        }
        $modelo->update(['publicado_em' => $referencia->subDays(140)]);

        return $modelo;
    }

    /**
     * @param  list<int>  $temporarias
     */
    private function aplicacao(Turma $turma, ModeloQuestionario $institucional, ModeloQuestionario $curso, array $temporarias, User $autor, CarbonImmutable $referencia, int $rodada, int $cenario): void
    {
        $aberta = $rodada === 4;
        $inicio = $referencia->subDays($aberta ? 1 : (4 - $rodada) * 45);
        $aplicacao = AplicacaoPesquisa::query()->create([
            'turma_id' => $turma->id,
            'nome' => '[DEMO] Rodada '.$rodada.' — '.$turma->nome_amigavel,
            'tipo_aplicacao' => TipoAplicacao::Monitoramento,
            'data_abertura' => $inicio,
            'data_encerramento' => $aberta ? $this->encerramentoDemonstracao() : $inicio->addDays(7),
            'criado_por_usuario_id' => $autor->id,
            'created_at' => $inicio->subDay(),
        ]);
        app(ComporAplicacaoPesquisa::class)->handle($aplicacao, $institucional, $curso, $temporarias);
        $perguntas = $aplicacao->perguntas()->orderBy('ordem')->get();

        foreach ($turma->estudantes()->orderBy('id')->get() as $indice => $estudante) {
            $status = ! $aberta || $indice >= 8
                ? StatusParticipacao::Finalizada
                : ($indice < 5 ? StatusParticipacao::NaoIniciada : StatusParticipacao::EmAndamento);
            $momento = $inicio->addHours($indice + 1);
            $participacao = $aplicacao->participacoes()->create([
                'estudante_id' => $estudante->id,
                'status' => $status,
                'iniciado_em' => $status === StatusParticipacao::NaoIniciada ? null : $momento,
                'finalizado_em' => $status === StatusParticipacao::Finalizada ? $momento->addMinutes(5) : null,
                'created_at' => $inicio,
            ]);
            if ($status === StatusParticipacao::NaoIniciada) {
                continue;
            }

            foreach ($perguntas as $posicao => $pergunta) {
                if ($status === StatusParticipacao::EmAndamento && $posicao >= 4) {
                    break;
                }
                $participacao->respostas()->create([
                    'aplicacao_pergunta_id' => $pergunta->id,
                    'valor_numerico' => $this->nota($cenario, $rodada, $indice, $posicao),
                    'respondido_em' => $momento->addMinutes(2),
                    'created_at' => $momento->addMinutes(2),
                ]);
            }
            if ($status !== StatusParticipacao::Finalizada) {
                continue;
            }
            if ($indice % 6 === 0) {
                $participacao->comentarioGeral()->create([
                    'comentario' => '[Dado fictício] '.match ($cenario) {
                        0 => 'As condições para as atividades práticas estão melhorando.',
                        1 => 'Estou me adaptando à rotina e conhecendo melhor os serviços de apoio.',
                        2 => 'Os projetos ajudam a relacionar os conteúdos com a prática.',
                        3 => 'Seria bom distribuir melhor os prazos de entrega dos trabalhos.',
                    },
                    'created_at' => $momento->addMinutes(5),
                ]);
            }
            if ($indice === 9) {
                $atendida = $rodada < 3;
                $participacao->solicitacaoAcompanhamento()->create([
                    'status' => $atendida ? StatusSolicitacaoAcompanhamento::Atendida : StatusSolicitacaoAcompanhamento::Pendente,
                    'solicitado_em' => $momento->addMinutes(5),
                    'responsavel_usuario_id' => $atendida ? $autor->id : null,
                    'atendido_em' => $atendida ? $momento->addDays(2) : null,
                    'created_at' => $momento->addMinutes(5),
                ]);
            }
        }
        $aplicacao->update(['status' => $aberta ? StatusAplicacao::Aberta : StatusAplicacao::Encerrada]);
    }

    private function nota(int $cenario, int $rodada, int $estudante, int $pergunta): int
    {
        $base = match ($cenario) {
            0 => $pergunta === 2 || $pergunta === 7 ? min(5, 1 + $rodada) : 4,
            1 => $pergunta === 3 || $pergunta === 4 ? min(5, 1 + $rodada) : 4,
            2 => 4,
            3 => $pergunta === 5 || $pergunta >= 10 ? max(2, 5 - $rodada) : 4,
        };

        return max(1, min(5, $base + (($estudante + $pergunta) % 3) - 1));
    }

    private function nomeEstudante(int $numero, string $curso, int $ano): string
    {
        $nomes = ['Ana', 'Bruno', 'Camila', 'Daniel', 'Eduarda', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'João', 'Karina', 'Lucas', 'Mariana', 'Nicolas', 'Olívia', 'Pedro', 'Rafaela', 'Samuel', 'Talita', 'Vinícius'];
        $sobrenomes = ['Silva', 'Souza', 'Lima', 'Oliveira', 'Santos'];

        return sprintf('[DEMO] %s %s (%s %d)', $nomes[$numero - 1], $sobrenomes[($numero - 1) % 5], $curso, $ano);
    }
}
