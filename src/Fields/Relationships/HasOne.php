<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Fields\Relationships;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\HasFieldsContract;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Contracts\HasUpdateOnPreviewContract;
use MoonShine\UI\Exceptions\FieldException;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Traits\WithFields;
use Throwable;

/**
 * @template-covariant R of HasOneOrMany|HasOneOrManyThrough
 * @extends ModelRelationField<R>
 * @implements HasFieldsContract<Fields>
 */
class HasOne extends ModelRelationField implements HasFieldsContract
{
    /** @use WithFields<Fields> */
    use WithFields;

    protected string $view = 'moonshine::fields.relationships.has-one';

    protected bool $toOne = true;

    protected bool $isGroup = true;

    protected bool $hasOld = false;

    protected bool $resolveValueOnce = true;

    protected bool $outsideComponent = true;

    protected bool $isAsync = true;

    public function hasWrapper(): bool
    {
        return false;
    }

    public function async(): static
    {
        $this->isAsync = true;

        return $this;
    }

    public function disableAsync(): static
    {
        $this->isAsync = false;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    /**
     * @return Fields
     * @throws Throwable
     */
    protected function prepareFields(): FieldsContract
    {
        if (! $this->hasFields()) {
            $fields = $this->getResource()->getDetailFields();

            $this->fields($fields->toArray());

            return $this->getFields();
        }

        return $this->getFields()
            ->onlyFields(withWrappers: true)
            ->detailFields(withOutside: false);
    }

    protected function resolveRawValue(): mixed
    {
        $items = [$this->toValue()];

        return collect($items)
            ->map(fn (Model $item) => data_get($item, $this->getResourceColumn()))
            ->implode(';');
    }

    /**
     * @throws Throwable
     */
    protected function resolvePreview(): Renderable|string
    {
        $items = [$this->toValue()];

        $resource = $this->getResource();

        return TableBuilder::make(items: $items)
            ->fields($this->getFieldsOnPreview())
            ->cast($resource->getCaster())
            ->preview()
            ->simple()
            ->vertical()
            ->render();
    }

    /**
     * HasOne/HasMany mapper with updateOnPreview
     */
    private function getFieldsOnPreview(): Closure
    {
        return function () {
            $fields = $this->getPreparedFields();

            // the onlyFields method is needed to exclude stack fields
            $fields->onlyFields()->each(function (FieldContract $field): void {
                if ($field instanceof HasUpdateOnPreviewContract && $field->isUpdateOnPreview()) {
                    $field->nowOnParams(params: ['relation' => $this->getRelationName()]);
                }

                $field->setParent($this);
            });

            return $fields->toArray();
        };
    }

    /**
     * @throws Throwable
     * @throws FieldException
     */
    protected function getComponent(): FormBuilder
    {
        $resource = $this->getResource();

        /** @var ?ModelResource $parentResource */
        $parentResource = moonshineRequest()->getResource();

        $item = $this->toValue();

        // When need lazy load
        // $item->load($resource->getWith());

        if (is_null($parentResource)) {
            throw new FieldException('Parent resource is required');
        }

        $parentItem = $parentResource->getItemOrInstance();
        /** @var HasOneOrMany|MorphOneOrMany $relation */
        $relation = $parentItem->{$this->getRelationName()}();

        $fields = $resource->getFormFields();
        $fields->onlyFields()->each(fn (FieldContract $field): FieldContract => $field->setParent($this));

        $action = $resource->getRoute(
            is_null($item) ? 'crud.store' : 'crud.update',
            $item?->getKey()
        );

        $redirectAfter = toPage(
            page: $parentResource->getFormPage(),
            resource: $parentResource,
            params: ['resourceItem' => $parentItem->getKey()]
        );

        $isAsync = ! is_null($item) && ($this->isAsync() || $resource->isAsync());

        return FormBuilder::make($action)
            ->reactiveUrl(
                static fn (): string => moonshineRouter()
                    ->getEndpoints()
                    ->reactive(page: $resource->getFormPage(), resource: $resource, extra: ['key' => $item?->getKey()])
            )
            ->name($resource->getUriKey())
            ->switchFormMode($isAsync)
            ->fields(
                $fields->when(
                    ! is_null($item),
                    static fn (Fields $fields): Fields => $fields->push(
                        Hidden::make('_method')->setValue('PUT'),
                    )
                )->push(
                    Hidden::make($relation->getForeignKeyName())
                        ->setValue($this->getRelatedModel()?->getKey())
                )->when(
                    $relation instanceof MorphOneOrMany,
                    fn (Fields $f) => $f->push(
                        /** @phpstan-ignore-next-line  */
                        Hidden::make($relation->getMorphType())->setValue($this->getRelatedModel()::class)
                    )
                )
                    ->toArray()
            )
            ->redirect($isAsync ? null : $redirectAfter)
            ->fillCast(
                $item?->toArray() ?? array_filter([
                $relation->getForeignKeyName() => $this->getRelatedModel()?->getKey(),
                ...$relation instanceof MorphOneOrMany
                    ? [$relation->getMorphType() => $this->getRelatedModel()?->getMorphClass()]
                    : [],
            ], static fn ($value) => filled($value)),
                $resource->getCaster()
            )
            ->buttons(
                is_null($item)
                    ? []
                    : [
                    $resource->getDeleteButton(
                        redirectAfterDelete: $redirectAfter,
                        isAsync: false,
                        modalName: "has-one-{$this->getRelationName()}",
                    )->class('btn-lg'),
                ]
            )
            ->onBeforeFieldsRender(static fn (Fields $fields): Fields => $fields->exceptElements(
                static fn (mixed $field): bool => $field instanceof ModelRelationField
                    && $field->isToOne()
                    && $field->getColumn() === $relation->getForeignKeyName()
            ))
            ->submit(__('moonshine::ui.save'), ['class' => 'btn-primary btn-lg']);
    }

    /**
     * @throws Throwable
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        $this->getResource()
            ->getFormFields()
            ->onlyFields()
            ->each(static fn (Field $field): mixed => $field->fillData($data)->afterDestroy($data));

        return $data;
    }

    /**
     * @throws FieldException
     * @return array<string, mixed>
     * @throws Throwable
     */
    protected function viewData(): array
    {
        return [
            'component' => $this->getComponent(),
        ];
    }
}
