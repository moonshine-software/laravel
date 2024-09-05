<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Buttons;

use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Forms\FiltersForm;
use MoonShine\Laravel\Resources\CrudResource;
use MoonShine\UI\Components\ActionButton;

final class FiltersButton
{
    public static function for(CrudResource $resource): ActionButtonContract
    {
        $form = moonshineConfig()->getForm('filters', FiltersForm::class, resource: $resource);

        $count = collect($resource->getFilterParams())
            ->filter(fn ($value): bool => (new self())->withoutEmptyFilter($value))
            ->count();

        return ActionButton::make(__('moonshine::ui.filters'), '#')
            ->name('filters-button')
            ->secondary()
            ->icon('adjustments-horizontal')
            ->inOffCanvas(
                static fn (): array|string|null => __('moonshine::ui.filters'),
                static fn (): FormBuilderContract => $form,
                name: 'filters-off-canvas',
                components: [$form]
            )
            ->showInLine()
            ->class('js-filter-button')
            ->when(
                $resource->isAsync() || $count,
                fn (ActionButtonContract $action): ActionButtonContract => $action->badge($count)
            );
    }

    private function withoutEmptyFilter(mixed $value): bool
    {
        if (is_iterable($value) && filled($value)) {
            return collect($value)
                ->filter(fn ($v): bool => $this->withoutEmptyFilter($v))
                ->isNotEmpty();
        }

        return ! blank($value) && $value !== '0';
    }
}
