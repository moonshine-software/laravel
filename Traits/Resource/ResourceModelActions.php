<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Traits\Resource;

use MoonShine\Laravel\Handlers\ExportHandler;
use MoonShine\Laravel\Handlers\Handler;
use MoonShine\Laravel\Handlers\ImportHandler;

trait ResourceModelActions
{
    /**
     * @return string[]
     */
    public function getActiveActions(): array
    {
        return ['create', 'view', 'update', 'delete', 'massDelete'];
    }

    /**
     * @return Handler
     */
    public function actions(): array
    {
        return [];
    }

    public function export(): ?Handler
    {
        if (! moonshineConfig()->isDefaultWithExport()) {
            return null;
        }

        return ExportHandler::make(__('moonshine::ui.export'))
            ->csv();
    }

    public function import(): ?Handler
    {
        if (! moonshineConfig()->isDefaultWithImport()) {
            return null;
        }

        return ImportHandler::make(__('moonshine::ui.import'));
    }

    /**
     * @return Handler
     */
    protected function handlers(): array
    {
        return array_filter([
            $this->export(),
            $this->import(),
        ]);
    }
}
