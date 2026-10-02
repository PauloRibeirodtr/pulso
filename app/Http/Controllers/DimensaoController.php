<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDimensaoRequest;
use App\Http\Requests\UpdateDimensaoRequest;
use App\Models\Dimensao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DimensaoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Dimensao::class);

        $dimensoes = Dimensao::query()->withCount('perguntas')->orderBy('nome')->paginate(20);

        return view('dimensoes.index', compact('dimensoes'));
    }

    public function create(): View
    {
        Gate::authorize('create', Dimensao::class);

        return view('dimensoes.form');
    }

    public function store(StoreDimensaoRequest $request): RedirectResponse
    {
        Dimensao::query()->create($request->validated());

        return redirect()->route('dimensoes.index')->with('status', 'Dimensão cadastrada.');
    }

    public function edit(Dimensao $dimensao): View
    {
        Gate::authorize('update', $dimensao);

        return view('dimensoes.form', compact('dimensao'));
    }

    public function update(UpdateDimensaoRequest $request, Dimensao $dimensao): RedirectResponse
    {
        $dimensao->update($request->validated());

        return redirect()->route('dimensoes.index')->with('status', 'Dimensão atualizada.');
    }
}
