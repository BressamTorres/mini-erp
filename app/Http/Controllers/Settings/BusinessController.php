<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.business', [
            'business' => $request->user()->business()->firstOrFail(),
        ]);
    }

    public function update(UpdateBusinessRequest $request): RedirectResponse
    {
        $request->user()->business()->firstOrFail()->update($request->validated());

        return to_route('settings.business.edit')
            ->with('success', 'Dados da empresa atualizados com sucesso.');
    }
}
