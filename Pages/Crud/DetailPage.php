<?php

namespace MoonShine\Laravel\Pages\Crud;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Core\Exceptions\PageException;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Components\Fragment;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\ModelRelationField;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\PageType;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Contracts\MoonShineRenderable;
use MoonShine\UI\Exceptions\MoonShineComponentException;
use Throwable;

/**
 * @method ModelResource getResource()
 * @extends Page<Fields>
 */
class DetailPage extends Page
{
    protected ?PageType $pageType = PageType::DETAIL;

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        if (! is_null($this->breadcrumbs)) {
            return $this->breadcrumbs;
        }

        $breadcrumbs = parent::getBreadcrumbs();

        $breadcrumbs[$this->getRoute()] = data_get($this->getResource()->getItem(), $this->getResource()->getColumn());

        return $breadcrumbs;
    }

    /**
     * @throws ResourceException
     */
    protected function prepareBeforeRender(): void
    {
        abort_if(
            ! $this->getResource()->hasAction(Action::VIEW)
            || ! $this->getResource()->can(Ability::VIEW),
            403
        );

        parent::prepareBeforeRender();
    }

    /**
     * @return list<MoonShineRenderable>
     * @throws Throwable
     */
    public function components(): array
    {
        $this->validateResource();
        $item = $this->getResource()->getItem();

        if (! $item?->exists) {
            oops404();
        }

        return $this->getLayers();
    }

    /**
     * @return list<MoonShineRenderable>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        $resource = $this->getResource();
        $item = $resource->getItem();

        return [
            Box::make([
                ...$this->getDetailComponents($item),
                LineBreak::make(),
                ...$this->getPageButtons(),
            ]),
        ];
    }

    /**
     * @return list<MoonShineRenderable>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        $components = [];
        $item = $this->getResource()->getItem();

        if (! $item?->exists) {
            return $components;
        }

        $outsideFields = $this->getResource()->getDetailFields(onlyOutside: true);

        if ($outsideFields->isNotEmpty()) {
            $components[] = LineBreak::make();

            /** @var ModelRelationField $field */
            foreach ($outsideFields as $field) {
                $field->fillCast(
                    $item,
                    $field->getResource()?->getModelCast()
                );

                $components[] = LineBreak::make();

                $blocks = [
                    Heading::make($field->getLabel()),
                    $field,
                ];

                if ($field->isToOne()) {
                    $field
                        ->withoutWrapper()
                        ->forcePreview();

                    $blocks = [
                        Box::make($field->getLabel(), [$field]),
                    ];
                }

                $components[] = Fragment::make($blocks)
                    ->name($field->getRelationName());
            }
        }

        return array_merge($components, $this->getResource()->getDetailPageComponents());
    }

    protected function getDetailComponent(?Model $item, Fields $fields): MoonShineRenderable
    {
        return TableBuilder::make($fields)
            ->cast($this->getResource()->getModelCast())
            ->items([$item])
            ->vertical()
            ->simple()
            ->preview();
    }

    /**
     * @return list<MoonShineRenderable>
     * @throws MoonShineComponentException
     * @throws PageException
     * @throws Throwable
     */
    protected function getDetailComponents(?Model $item): array
    {
        return [
            Fragment::make([
                $this->getDetailComponent($item, $this->getResource()->getDetailFields()),
            ])->name('crud-detail'),
        ];
    }

    protected function getPageButtons(): array
    {
        return [
            ActionGroup::make($this->getResource()->getDetailItemButtons())
                ->fill($this->getResource()->getCastedItem())
                ->customAttributes(['class' => 'justify-end']),
        ];
    }
}
