<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Show the business settings.
     */
    public function edit(): View
    {
        return view('modules.configuracion.edit', [
            'setting' => Setting::current(),
        ]);
    }

    /**
     * Update the business settings.
     */
    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        Setting::current()->update($request->validated());

        return redirect()
            ->route('configuracion.edit')
            ->with('status', 'Configuración actualizada.');
    }
}
