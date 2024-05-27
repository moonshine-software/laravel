<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Traits\Resource;

use Illuminate\Support\Collection;
use MoonShine\Core\Contracts\Fields\FieldsWrapper;
use MoonShine\Laravel\Applies\FieldsWithoutFilters;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Exceptions\FilterException;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Support\Enums\PageType;
use MoonShine\UI\Fields\Field;
use Throwable;

trait ResourceWithFields
{
    /**
     * @return Field
     */
    public function indexFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getIndexFields(): Fields
    {
        $fields = $this->getPages()
            ->findByType(PageType::INDEX)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->indexFields());
        }

        return $fields->ensure([Field::class, FieldsWrapper::class]);
    }

    /**
     * @return Field
     */
    public function formFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getFormFields(bool $withOutside = false): Fields
    {
        $fields = $this->getPages()
            ->findByType(PageType::FORM)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->formFields());
        }

        return $fields->formFields(withOutside: $withOutside);
    }

    /**
     * @return Field
     */
    public function detailFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getDetailFields(bool $withOutside = false, bool $onlyOutside = false): Fields
    {
        /**
         * @var Fields $fields
         */
        $fields = $this->getPages()
            ->findByType(PageType::DETAIL)
            ?->getFields();

        if ($fields->isEmpty()) {
            $fields = Fields::make($this->detailFields());
        }

        return $fields
            ->ensure([Field::class, ModelRelationField::class, FieldsWrapper::class])
            ->detailFields(withOutside: $withOutside, onlyOutside: $onlyOutside);
    }

    /**
     * @return Collection<int, ModelRelationField>
     * @throws Throwable
     */
    public function getOutsideFields(): Fields
    {
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
     * @return Field
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getFilters(): Fields
    {
        $filters = Fields::make($this->filters())
            ->withoutOutside()
            ->wrapNames('filters');

        $filters->each(function ($filter): void {
            if (in_array($filter::class, FieldsWithoutFilters::LIST)) {
                throw new FilterException("You can't use " . $filter::class . " inside filters.");
            }
        });

        return $filters;
    }

    /**
     * @return Field
     */
    public function exportFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getExportFields(): Fields
    {
        return Fields::make($this->exportFields())->ensure(Field::class);
    }

    /**
     * @return Field
     */
    public function importFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, Field>
     * @throws Throwable
     */
    public function getImportFields(): Fields
    {
        return Fields::make($this->importFields())->ensure(Field::class);
    }
}
