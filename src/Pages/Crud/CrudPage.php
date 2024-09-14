<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Pages\Crud;

use MoonShine\Contracts\Core\CrudPageContract;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Pages\Page;

abstract class CrudPage extends Page implements CrudPageContract
{
    /**
     * @return list<ComponentContract>
     */
    protected function fields(): iterable
    {
        return [];
    }

    /**
     * @return Fields
     */
    public function getFields(): FieldsContract
    {
        return $this->getCore()->getFieldsCollection($this->fields());
    }
}
