<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampusRequest;
use App\Http\Requests\UpdateCampusRequest;
use App\Models\Campus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CampusController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Campus::class);

        $campi = Campus::query()
            ->visiveisPara($request->user())
            ->select(['id', 'nome', 'sigla', 'ativo', 'created_at'])
            ->withCount('cursos')
            ->when($request->string('busca')->trim()->isNotEmpty(), function (Builder $query) use ($request): void {
                $busca = $request->string('busca')->trim()->toString();

                $query->where(function (Builder $query) use ($busca): void {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('sigla', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('campi.index', compact('campi'));
    }

    public function create(): View
    {
        Gate::authorize('create', Campus::class);

        return view('campi.create');
    }

    public function store(StoreCampusRequest $request): RedirectResponse
    {
        Campus::query()->create($request->validated());

        return redirect()->route('campi.index')
            ->with('status', 'Campus cadastrado com sucesso.');
    }

    public function edit(Campus $campus): View
    {
        Gate::authorize('update', $campus);

        return view('campi.edit', compact('campus'));
    }

    public function update(UpdateCampusRequest $request, Campus $campus): RedirectResponse
    {
        $campus->update($request->validated());

        return redirect()->route('campi.index')
            ->with('status', 'Campus atualizado com sucesso.');
    }
}
