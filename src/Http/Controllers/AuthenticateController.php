<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Http\Requests\LoginFormRequest;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\Pages\LoginPage;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthenticateController extends MoonShineController
{
    public function login(): Renderable|RedirectResponse|string
    {
        if ($this->auth()->check()) {
            return redirect(
                moonshineRouter()->getEndpoints()->home()
            );
        }

        return moonshineConfig()
            ->getPage('login', LoginPage::class)
            ->loaded()
            ->render();
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(LoginFormRequest $request): RedirectResponse|JsonResponse
    {
        if (filled(moonshineConfig()->getAuthPipelines())) {
            $request = Pipeline::send($request)->through(
                array_filter(
                    moonshineConfig()->getAuthPipelines()
                )
            )->thenReturn();
        }

        if ($request instanceof MoonShineJsonResponse) {
            return $request;
        }

        if ($request instanceof RedirectResponse) {
            return $request;
        }

        $request->authenticate();

        return redirect()->intended(
            moonshineRouter()->getEndpoints()->home()
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(
            moonshineRouter()->to('login')
        );
    }
}
