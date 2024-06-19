<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Traits\Fields;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MoonShine\Support\DTOs\Select\Options;
use MoonShine\UI\Exceptions\FieldException;
use Throwable;

trait WithRelatedValues
{
    protected array $values = [];

    protected ?Collection $memoizeValues = null;

    protected ?Closure $valuesQuery = null;

    protected array $relatedColumns = [];

    protected function relatedColumns(array $relatedColumns): static
    {
        $this->relatedColumns = $relatedColumns;

        return $this;
    }

    protected function getMemoizeValues(): Collection
    {
        return $this->memoizeValues ?? collect();
    }

    public function valuesQuery(Closure $callback): static
    {
        $this->valuesQuery = $callback;

        return $this;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * @throws Throwable
     */
    public function resolveValuesQuery(): Builder
    {
        $relation = $this->getRelation();

        throw_if(
            is_null($relation),
            new FieldException('Relation is required')
        );

        $related = $relation->getRelated();
        $query = $related->newModelQuery();

        if (! is_null($this->valuesQuery)) {
            $query = value($this->valuesQuery, $query, $this);
        }

        return $query;
    }

    protected function resolveRelatedQuery(Builder $builder): Collection
    {
        // #MongoDB Models fix
        $key = rescue(static fn () => $builder->toRawSql(), static fn (): bool => false, false);

        if($key === false) {
            return $builder->get();
        }

        return moonshineCache()->remember(
            sha1((string) $key),
            4,
            static fn (): Collection => $builder->get()
        );
    }

    protected function getSelectedValue(): string|array
    {
        return (string) $this->getValue();
    }

    /**
     * @throws Throwable
     */
    public function getValues(): Options
    {
        $query = $this->resolveValuesQuery();

        $formatted = ! is_null($this->getFormattedValueCallback());

        $values = $this->memoizeValues ?? $this->resolveRelatedQuery($query);
        $this->memoizeValues = $values;

        $getValue = fn (Model $item) => $formatted ? value(
            $this->getFormattedValueCallback(),
            $item,
            $this
        ) : data_get($item, $this->getResourceColumn());

        $values = $values->mapWithKeys(
            static fn ($item): array => [
                $item->getKey() => $getValue($item),
            ]
        );

        $toOptions = fn (array $values): Options => new Options(
            $values,
            $this->getSelectedValue(),
            $this->getValuesWithProperties(onlyCustom: true)->toArray()
        );

        if ($values->isNotEmpty()) {
            return $toOptions(
                $values->toArray()
            );
        }

        $value = $this->toValue();

        // if the values are empty then we add the selected one
        if ($value instanceof Model && $value->exists && $values->isEmpty()) {
            $values->put(
                $value->getKey(),
                $getValue($value)
            );
        }

        return $toOptions(
            $values->toArray(),
        );
    }
}
