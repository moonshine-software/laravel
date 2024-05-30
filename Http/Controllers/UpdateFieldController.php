<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Http\Controllers;

use Illuminate\Http\Response;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\Collections\Fields;
use MoonShine\Laravel\Http\Requests\Relations\RelationModelColumnUpdateRequest;
use MoonShine\Laravel\Http\Requests\Resources\UpdateColumnFormRequest;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Contracts\Fields\HasFields;
use MoonShine\UI\Exceptions\FieldException;
use MoonShine\UI\Fields\Field;
use Throwable;

class UpdateFieldController extends MoonShineController
{
    /**
     * @throws Throwable
     */
    public function column(UpdateColumnFormRequest $request): Response
    {
        return $this->save($request->getResource(), $request->getField());
    }

    /**
     * @throws Throwable
     */
    public function relation(RelationModelColumnUpdateRequest $request): Response
    {
        $relationField = $request->getField();

        if($relationField instanceof HasFields) {
            $relationField->preparedFields();
        }

        $resource = $relationField->getResource();

        $field = $relationField
            ->getFields()
            ?->onlyFields()
            ?->findByColumn($request->get('field'));

        throw_if(
            is_null($field) || is_null($resource),
            new FieldException('Resource and Field is required')
        );

        return $this->save($resource, $field);
    }

    protected function save(ModelResource $resource, Field $field)
    {
        try {
            $resource->save(
                $resource->getItemOrFail(),
                Fields::make([$field])
            );
        } catch (ResourceException $e) {
            throw_if(! moonshine()->isProduction(), $e);
            report_if(moonshine()->isProduction(), $e);

            return response($e->getMessage());
        }

        return response()->noContent();
    }
}
