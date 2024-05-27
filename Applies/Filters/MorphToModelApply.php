<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Applies\Filters;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Core\Contracts\ApplyContract;
use MoonShine\Laravel\Fields\Relationships\MorphTo;
use MoonShine\UI\Fields\Field;

class MorphToModelApply implements ApplyContract
{
    /** @param MorphTo $field */
    public function apply(Field $field): Closure
    {
        return static function (Builder $query) use ($field): void {
            $query->whereMorphRelation(
                $field->getRelationName(),
                [$field->requestTypeValue()],
                $field->getColumn(),
                '=',
                $field->requestValue(),
            );
        };
    }
}
