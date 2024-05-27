<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Fields\Relationships;

/**
 * @extends ModelRelationField<\Illuminate\Database\Eloquent\Relations\MorphToMany>
 */
class MorphToMany extends BelongsToMany
{
    protected bool $isMorph = true;
}
