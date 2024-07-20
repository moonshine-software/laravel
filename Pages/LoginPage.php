<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Pages;

use MoonShine\Laravel\Forms\LoginForm;
use MoonShine\Laravel\Layouts\LoginLayout;

class LoginPage extends Page
{
    protected ?string $layout = LoginLayout::class;

    public function components(): array
    {
        return [
            moonshineConfig()->getForm('login', LoginForm::class),
        ];
    }
}
