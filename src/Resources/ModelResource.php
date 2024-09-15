<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Resources;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Gate;
use MoonShine\Contracts\Core\CrudPageContract;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\Core\TypeCasts\DataCasterContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Contracts\Resource\HasHandlersContract;
use MoonShine\Laravel\Contracts\Resource\HasQueryTagsContract;
use MoonShine\Laravel\Contracts\Resource\WithQueryBuilderContract;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Traits\Resource\ResourceModelQuery;
use MoonShine\Laravel\TypeCasts\ModelCaster;
use Throwable;

/**
 * @template TData of Model
 * @template-covariant TIndexPage of CrudPageContract
 * @template-covariant TFormPage of CrudPageContract
 * @template-covariant TDetailPage of CrudPageContract
 *
 * @extends CrudResource<TData, TIndexPage, TFormPage, TDetailPage, Fields, Enumerable>
 */
abstract class ModelResource extends CrudResource implements
    HasQueryTagsContract,
    HasHandlersContract,
    WithQueryBuilderContract
{
    /**
     * @use ResourceModelQuery<TData>
     */
    use ResourceModelQuery;

    protected string $model;

    public function flushState(): void
    {
        parent::flushState();

        $this->queryBuilder = null;
        $this->customQueryBuilder = null;
    }

    /**
     * @return TData
     */
    public function getModel(): Model
    {
        return new $this->model();
    }

    public function getDataInstance(): mixed
    {
        return $this->getModel();
    }

    /**
     * @return ModelCaster<TData>
     */
    public function getCaster(): DataCasterContract
    {
        return new ModelCaster($this->model);
    }

    protected function isCan(Ability $ability): bool
    {
        if (! moonshineConfig()->isAuthEnabled()) {
            return true;
        }

        $user = MoonShineAuth::getGuard()->user();

        $checkCustomRules = moonshineConfig()
            ->getAuthorizationRules()
            ->every(fn ($rule) => $rule($this, $user, $ability, $this->getItem() ?? $this->getDataInstance()));

        if (! $checkCustomRules) {
            return false;
        }

        if (! $this->isWithPolicy()) {
            return true;
        }

        return Gate::forUser($user)
            ->allows($ability->value, $this->getItem() ?? $this->getDataInstance());
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function massDelete(array $ids): void
    {
        $this->beforeMassDeleting($ids);

        $this->getDataInstance()
            ->newModelQuery()
            ->whereIn($this->getDataInstance()->getKeyName(), $ids)
            ->get()
            ->each(function (mixed $item): bool {
                $item = $this->beforeDeleting($item);

                return (bool) tap($item->delete(), fn (): mixed => $this->afterDeleted($item));
            });

        $this->afterMassDeleted($ids);
    }

    /**
     * @param TData $item
     * @param ?Fields $fields
     * @throws Throwable
     */
    public function delete(mixed $item, ?FieldsContract $fields = null): bool
    {
        $item = $this->beforeDeleting($item);

        $fields ??= $this->getFormFields()->onlyFields();

        $fields->fill($item->toArray(), $this->getCaster()->cast($item));

        $fields->each(static fn (FieldContract $field): mixed => $field->afterDestroy($item));

        if ($this->isDeleteRelationships()) {
            $this->getOutsideFields()->each(static function (ModelRelationField $field) use ($item): void {
                $relationItems = $item->{$field->getRelationName()};

                ! $field->isToOne() ?: $relationItems = collect([$relationItems]);

                $relationItems->each(
                    static fn (mixed $relationItem): mixed => $field->afterDestroy($relationItem)
                );
            });
        }

        return (bool) tap($item->delete(), fn (): mixed => $this->afterDeleted($item));
    }

    /**
     * @param TData $item
     * @param ?Fields $fields
     * @return TData
     *
     * @throws ResourceException
     * @throws Throwable
     */
    public function save(mixed $item, ?FieldsContract $fields = null): mixed
    {
        $fields ??= $this->getFormFields()->onlyFields();

        $fields->fill($item->toArray(), $this->getCaster()->cast($item));

        try {
            $fields->each(static fn (FieldContract $field): mixed => $field->beforeApply($item));

            if (! $item->exists) {
                $item = $this->beforeCreating($item);
            }

            if ($item->exists) {
                $item = $this->beforeUpdating($item);
            }

            $fields->withoutOutside()
                ->each(fn (FieldContract $field): mixed => $field->apply($this->fieldApply($field), $item));

            if ($item->save()) {
                $this->isRecentlyCreated = $item->wasRecentlyCreated;

                $item = $this->afterSave($item, $fields);
            }
        } catch (QueryException $queryException) {
            throw new ResourceException($queryException->getMessage());
        }

        $this->setItem($item);

        return $item;
    }

    public function fieldApply(FieldContract $field): Closure
    {
        /**
         * @param TData $item
         * @return TData
         */
        return static function (mixed $item) use ($field): mixed {
            if (! $field->hasRequestValue() && ! $field->getDefaultIfExists()) {
                return $item;
            }

            $value = $field->getRequestValue() !== false ? $field->getRequestValue() : null;

            data_set($item, $field->getColumn(), $value);

            return $item;
        };
    }

    /**
     * @param TData $item
     * @param Fields $fields
     * @return TData
     */
    protected function afterSave(mixed $item, FieldsContract $fields): mixed
    {
        $wasRecentlyCreated = $this->isRecentlyCreated();

        $fields->each(static fn (FieldContract $field): mixed => $field->afterApply($item));

        if ($item->isDirty()) {
            $item->save();
        }

        if ($wasRecentlyCreated) {
            $item = $this->afterCreated($item);
        }

        if (! $wasRecentlyCreated) {
            $item = $this->afterUpdated($item);
        }

        return $item;
    }
}
