<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Fields\Relationships;

/**
 * @extends HasMany<\Illuminate\Database\Eloquent\Relations\MorphMany>
 */
class MorphMany extends HasMany
{
    protected bool $isMorph = true;
}
