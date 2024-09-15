<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Traits\Resource;

use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Applies\FieldsWithoutFilters;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Exceptions\FilterException;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Support\Enums\PageType;
use MoonShine\UI\Contracts\FieldsWrapperContract;
use Throwable;

trait ResourceWithFields
{
    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [];
    }

    /**
     * @throws Throwable
     */
    public function getIndexFields(): Fields
    {
        /** @var Fields $fields */
        $fields = $this->getPages()
            ->findByType(PageType::INDEX)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->indexFields());
        }

        $fields->ensure([FieldContract::class, FieldsWrapperContract::class]);

        return $fields;
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): iterable
    {
        return [];
    }

    /**
     * @throws Throwable
     */
    public function getFormFields(bool $withOutside = false): Fields
    {
        /** @var Fields $fields */
        $fields = $this->getPages()
            ->findByType(PageType::FORM)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->formFields());
        }

        return $fields->formFields(withOutside: $withOutside);
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [];
    }

    /**
     * @throws Throwable
     */
    public function getDetailFields(bool $withOutside = false, bool $onlyOutside = false): Fields
    {
        /** @var Fields $fields */
        $fields = $this->getPages()
            ->findByType(PageType::DETAIL)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->detailFields());
        }

        $fields->ensure([FieldsWrapperContract::class, FieldContract::class, ModelRelationField::class]);

        return $fields->detailFields(withOutside: $withOutside, onlyOutside: $onlyOutside);
    }

    /**
     * @return Fields<int, ModelRelationField>
     * @throws Throwable
     */
    public function getOutsideFields(): Fields
    {
        /**
         * @var Fields $fields
         */
        $fields = $this->getPages()
            ->findByType(PageType::FORM)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->formFields());
        }

        return $fields
            ->onlyFields()
            ->onlyOutside();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [];
    }

    public function hasFilters(): bool
    {
        return $this->filters() !== [];
    }

    /**
     * @throws Throwable
     */
    public function getFilters(): Fields
    {
        $filters = Fields::make($this->filters())
            ->withoutOutside()
            ->wrapNames('filter');

        $filters->each(static function ($filter): void {
            if (in_array($filter::class, FieldsWithoutFilters::LIST)) {
                throw new FilterException("You can't use " . $filter::class . " inside filters.");
            }
        });

        return $filters;
    }
}
