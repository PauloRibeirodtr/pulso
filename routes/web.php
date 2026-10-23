<?php

use App\Http\Controllers\AplicacaoPesquisaController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DimensaoController;
use App\Http\Controllers\EstudanteController;
use App\Http\Controllers\EstudantePesquisaController;
use App\Http\Controllers\ExportarIndicadoresController;
use App\Http\Controllers\IndicadorController;
use App\Http\Controllers\ModeloQuestionarioController;
use App\Http\Controllers\ModeloQuestionarioPerguntaController;
use App\Http\Controllers\PerguntaController;
use App\Http\Controllers\RegistroAuditoriaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ResetEstudantePasswordController;
use App\Http\Controllers\ServidorController;
use App\Http\Controllers\SolicitacaoAcompanhamentoController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\UsuarioPermissaoController;
use App\Http\Middleware\EnsureUserIsActive;
use App\TipoUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('/dashboard', function (Request $request): RedirectResponse {
        return redirect()->route(
            $request->user()->tipo_usuario === TipoUsuario::Estudante
                ? 'estudante.dashboard'
                : 'servidor.dashboard',
        );
    })
        ->name('dashboard');

    Route::get('/estudante/inicio', [EstudantePesquisaController::class, 'index'])
        ->name('estudante.dashboard');
    Route::get('/estudante/pesquisas/{participacao}', [EstudantePesquisaController::class, 'show'])
        ->name('estudante.pesquisas.show');
    Route::post('/estudante/pesquisas/{participacao}/salvar', [EstudantePesquisaController::class, 'save'])
        ->name('estudante.pesquisas.save');
    Route::post('/estudante/pesquisas/{participacao}/finalizar', [EstudantePesquisaController::class, 'finish'])
        ->name('estudante.pesquisas.finish');

    Route::view('/servidor/inicio', 'dashboard')
        ->name('servidor.dashboard');

    Route::view('/minha-senha', 'auth.change-password')
        ->name('password.change');

    Route::resources([
        'campi' => CampusController::class,
        'cursos' => CursoController::class,
        'turmas' => TurmaController::class,
        'estudantes' => EstudanteController::class,
    ], [
        'only' => ['index', 'create', 'store', 'edit', 'update'],
        'parameters' => [
            'campi' => 'campus',
            'cursos' => 'curso',
            'turmas' => 'turma',
            'estudantes' => 'estudante',
        ],
    ]);

    Route::resource('servidores', ServidorController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['servidores' => 'servidor']);
    Route::post('/servidores/{servidor}/permissoes', [UsuarioPermissaoController::class, 'store'])
        ->name('servidores.permissoes.store');
    Route::get('/permissoes/{usuarioPermissao}/editar', [UsuarioPermissaoController::class, 'edit'])
        ->name('permissoes.edit');
    Route::put('/permissoes/{usuarioPermissao}', [UsuarioPermissaoController::class, 'updateDetails'])
        ->name('permissoes.details.update');
    Route::patch('/permissoes/{usuarioPermissao}', [UsuarioPermissaoController::class, 'update'])
        ->name('permissoes.update');

    Route::get('/estudantes/importar', [EstudanteController::class, 'importForm'])
        ->name('estudantes.import.form');
    Route::post('/estudantes/importar', [EstudanteController::class, 'import'])
        ->name('estudantes.import');

    Route::put(
        '/estudantes/{estudante}/senha',
        ResetEstudantePasswordController::class,
    )->name('estudantes.password.reset');

    Route::resource('dimensoes', DimensaoController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['dimensoes' => 'dimensao']);
    Route::resource('perguntas', PerguntaController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['perguntas' => 'pergunta']);
    Route::resource('modelos-questionario', ModeloQuestionarioController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['modelos-questionario' => 'modeloQuestionario']);
    Route::post('/modelos-questionario/{modeloQuestionario}/publicar', [ModeloQuestionarioController::class, 'publish'])
        ->name('modelos-questionario.publish');
    Route::post('/modelos-questionario/{modeloQuestionario}/duplicar', [ModeloQuestionarioController::class, 'duplicate'])
        ->name('modelos-questionario.duplicate');
    Route::patch('/modelos-questionario/{modeloQuestionario}/situacao', [ModeloQuestionarioController::class, 'changeStatus'])
        ->name('modelos-questionario.status');
    Route::post('/modelos-questionario/{modeloQuestionario}/perguntas', [ModeloQuestionarioPerguntaController::class, 'store'])
        ->name('modelos-questionario.perguntas.store');
    Route::delete('/modelos-questionario/{modeloQuestionario}/perguntas/{item}', [ModeloQuestionarioPerguntaController::class, 'destroy'])
        ->name('modelos-questionario.perguntas.destroy');
    Route::patch('/modelos-questionario/{modeloQuestionario}/perguntas/{item}/{direcao}', [ModeloQuestionarioPerguntaController::class, 'move'])
        ->name('modelos-questionario.perguntas.move');

    Route::resource('aplicacoes', AplicacaoPesquisaController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['aplicacoes' => 'aplicacao']);
    Route::post('/aplicacoes/{aplicacao}/abrir', [AplicacaoPesquisaController::class, 'open'])
        ->name('aplicacoes.open');
    Route::post('/aplicacoes/{aplicacao}/encerrar', [AplicacaoPesquisaController::class, 'close'])
        ->name('aplicacoes.close');
    Route::patch('/aplicacoes/{aplicacao}/prazo', [AplicacaoPesquisaController::class, 'extend'])
        ->name('aplicacoes.extend');

    Route::get('/indicadores', [IndicadorController::class, 'index'])
        ->name('indicadores.index');
    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('/relatorios/exportar', [RelatorioController::class, 'export'])->name('relatorios.export');
    Route::get('/indicadores/aplicacoes/{aplicacao}', [IndicadorController::class, 'show'])
        ->name('indicadores.show');
    Route::get('/indicadores/aplicacoes/{aplicacao}/exportar', ExportarIndicadoresController::class)
        ->name('indicadores.export');
    Route::get('/auditoria', [RegistroAuditoriaController::class, 'index'])
        ->name('auditoria.index');
    Route::get('/solicitacoes', [SolicitacaoAcompanhamentoController::class, 'index'])
        ->name('solicitacoes.index');
    Route::patch('/solicitacoes/{solicitacao}/atender', [SolicitacaoAcompanhamentoController::class, 'attend'])
        ->name('solicitacoes.attend');
});
