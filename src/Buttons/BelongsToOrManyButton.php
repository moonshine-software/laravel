<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Buttons;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Modal;
use MoonShine\UI\Fields\Hidden;
use Throwable;

final class BelongsToOrManyButton
{
    /**
     * @throws Throwable
     */
    public static function for(
        BelongsToMany|BelongsTo $field,
        ?ActionButtonContract $button = null
    ): ActionButtonContract {
        /** @var ModelResource $resource */
        $resource = $field->getResource();

        if (! $resource->getFormPage()) {
            return ActionButton::emptyHidden();
        }

        $action = $resource->getRoute('crud.store');

        $getFields = static function () use ($resource, $field) {
            $fields = $resource->getFormFields();

            $fields->onlyFields()
                ->each(static fn (FieldContract $nestedFields): FieldContract => $nestedFields->setParent($field));

            return $fields
                ->push(Hidden::make('_async_field')->setValue(true))
                ->toArray();
        };

        $actionButton = $button
            ? $button->setUrl($action)
            : ActionButton::make(__('moonshine::ui.add'), url: $action);

        return $actionButton
            ->name("belongs-to-many-{$field->getRelationName()}-button")
            ->canSee(static fn (): bool => $resource->hasAction(Action::CREATE) && $resource->can(Ability::CREATE))
            ->inModal(
                title: static fn (): array|string => __('moonshine::ui.create'),
                content: static fn (?Model $data): string => (string) FormBuilder::make($action)
                    ->reactiveUrl(
                        moonshineRouter()->getEndpoints()->reactive($resource->getFormPage(), $resource)
                    )
                    ->switchFormMode(
                        true,
                        [
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, $field->getRelationName()),
                            AlpineJs::event(JsEvent::FORM_RESET, $resource->getUriKey()),
                        ]
                    )
                    ->name($resource->getUriKey())
                    ->fillCast(
                        [],
                        $resource->getCaster()
                    )
                    ->submit(__('moonshine::ui.save'), ['class' => 'btn-primary btn-lg'])
                    /** @phpstan-ignore-next-line  */
                    ->fields($getFields),
                name: "modal-belongs-to-many-{$field->getRelationName()}",
                builder: static fn (Modal $modal): Modal => $modal->wide()->closeOutside(false)
            )
            ->primary()
            ->icon('plus');
    }
}
