<?php

namespace App\Http\Controllers;

use App\Models\RegistroAuditoria;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RegistroAuditoriaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RegistroAuditoria::class);

        $registros = RegistroAuditoria::query()
            ->with('usuario:id,name,identificador_institucional')
            ->orderByDesc('id')
            ->paginate(30);

        return view('auditoria.index', compact('registros'));
    }
}
