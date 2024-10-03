<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Buttons;

use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Resources\CrudResource;
use MoonShine\UI\Components\ActionButton;

final class CreateButton
{
    public static function for(
        CrudResource $resource,
        ?string $componentName = null,
        bool $isAsync = true,
        string $modalName = 'create-modal',
    ): ActionButtonContract {
        if (! $resource->getFormPage()) {
            return ActionButton::emptyHidden();
        }

        $action = $resource->getFormPageUrl();

        if ($resource->isCreateInModal()) {
            $action = $resource->getFormPageUrl(
                params: [
                    '_component_name' => $componentName ?? $resource->getListComponentName(),
                    '_async_form' => $isAsync,
                ],
                fragment: 'crud-form'
            );
        }

        return ActionButton::make(
            __('moonshine::ui.create'),
            $action
        )
            ->name('resource-create-button')
            ->when(
                $resource->isCreateInModal(),
                static fn (ActionButtonContract $button): ActionButtonContract => $button->async()->inModal(
                    static fn (): array|string => __('moonshine::ui.create'),
                    static fn (): string => '',
                    name: $modalName
                )
            )
            ->canSee(
                static fn (): bool => $resource->hasAction(Action::CREATE)
                && $resource->can(Ability::CREATE)
            )
            ->primary()
            ->icon('plus');
    }
}
