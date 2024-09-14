<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Applies\Filters;

use MoonShine\UI\Fields\Json;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\UI\ApplyContract;
use MoonShine\Contracts\UI\FieldContract;

/**
 * @implements ApplyContract<Json>
 */
class JsonModelApply implements ApplyContract
{
    /* @param  \MoonShine\UI\Fields\Json  $field */
    public function apply(FieldContract $field): Closure
    {
        return static function (Builder $query) use ($field): void {
            $values = collect($field->getRequestValue())
                ->filter();

            if ($values->isNotEmpty()) {
                $query->where(static function (Builder $q) use ($values, $field): void {
                    $data = array_filter($values->first());
                    $column = str_replace('.', '->', $field->getColumn());

                    if (filled($data)) {
                        $q
                            ->when(
                                ! $field->isKeyOrOnlyValue(),
                                static fn (Builder $qq) => $qq->whereJsonContains($column, $data)
                            )
                            ->when(
                                $field->isKeyValue(),
                                static fn (Builder $qq) => $qq->where(
                                    $column . '->' . ($data['key'] ?? '*'),
                                    $data['value'] ?? ''
                                )
                            )
                            ->when(
                                $field->isOnlyValue(),
                                static fn (Builder $qq) => $qq->whereJsonContains($column, $data['value'] ?? '')
                            );
                    }
                });
            }
        };
    }
}
