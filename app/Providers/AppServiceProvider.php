<?php

namespace App\Providers;

use App\Auth\ServidorUserProvider;
use App\Models\AplicacaoPesquisa;
use App\Models\Campus;
use App\Models\Curso;
use App\Models\Dimensao;
use App\Models\Estudante;
use App\Models\ModeloQuestionario;
use App\Models\ParticipacaoPesquisa;
use App\Models\Pergunta;
use App\Models\RegistroAuditoria;
use App\Models\SolicitacaoAcompanhamento;
use App\Models\Turma;
use App\Models\User;
use App\Models\UsuarioPermissao;
use App\Observers\RegistrarAuditoria;
use App\Policies\AplicacaoPesquisaPolicy;
use App\Policies\CampusPolicy;
use App\Policies\CursoPolicy;
use App\Policies\DimensaoPolicy;
use App\Policies\EstudantePolicy;
use App\Policies\ModeloQuestionarioPolicy;
use App\Policies\ParticipacaoPesquisaPolicy;
use App\Policies\PerguntaPolicy;
use App\Policies\RegistroAuditoriaPolicy;
use App\Policies\SolicitacaoAcompanhamentoPolicy;
use App\Policies\TurmaPolicy;
use App\Policies\UserPolicy;
use App\Policies\UsuarioPermissaoPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider(
            'servidores',
            fn (Application $app, array $config): ServidorUserProvider => new ServidorUserProvider(
                $app->make(Hasher::class),
                $config['model'],
            ),
        );

        Gate::policy(Estudante::class, EstudantePolicy::class);
        Gate::policy(Campus::class, CampusPolicy::class);
        Gate::policy(AplicacaoPesquisa::class, AplicacaoPesquisaPolicy::class);
        Gate::policy(Curso::class, CursoPolicy::class);
        Gate::policy(Dimensao::class, DimensaoPolicy::class);
        Gate::policy(Pergunta::class, PerguntaPolicy::class);
        Gate::policy(RegistroAuditoria::class, RegistroAuditoriaPolicy::class);
        Gate::policy(SolicitacaoAcompanhamento::class, SolicitacaoAcompanhamentoPolicy::class);
        Gate::policy(ModeloQuestionario::class, ModeloQuestionarioPolicy::class);
        Gate::policy(ParticipacaoPesquisa::class, ParticipacaoPesquisaPolicy::class);
        Gate::policy(Turma::class, TurmaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UsuarioPermissao::class, UsuarioPermissaoPolicy::class);

        UsuarioPermissao::observe(RegistrarAuditoria::class);
        AplicacaoPesquisa::observe(RegistrarAuditoria::class);
        ModeloQuestionario::observe(RegistrarAuditoria::class);
        SolicitacaoAcompanhamento::observe(RegistrarAuditoria::class);
    }
}
