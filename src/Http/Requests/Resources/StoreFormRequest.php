<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Http\Requests\Resources;

use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use Throwable;

final class StoreFormRequest extends MoonShineFormRequest
{
    /**
     * @throws ResourceException
     * @throws Throwable
     */
    public function authorize(): bool
    {
        $this->beforeResourceAuthorization();

        if (! $this->getResource()?->hasAction(Action::CREATE)) {
            return false;
        }

        return (bool) $this->getResource()?->can(Ability::CREATE);
    }

    public function rules(): array
    {
        return $this->getResource()?->getRules() ?? [];
    }
}
