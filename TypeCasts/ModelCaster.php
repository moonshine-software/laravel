<?php

declare(strict_types=1);

namespace MoonShine\Laravel\TypeCasts;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\Paginator\PaginatorContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\Core\TypeCasts\DataCasterContract;
use MoonShine\Core\Exceptions\MoonShineException;

/**
 * @template-covariant T of Model
 */
final readonly class ModelCaster implements DataCasterContract
{
    public function __construct(
        /** @var class-string<T> $class */
        private string $class
    ) {
    }

    /** @return class-string<T> $class */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return DataWrapperContract<T>
     */
    public function cast(mixed $data): DataWrapperContract
    {
        if(is_array($data)) {
            /** @var T $model */
            $model = new ($this->getClass());
            $data = $model->forceFill($data);
            $data->exists = ! empty($data->getKey());
        }

        return new ModelDataWrapper($data);
    }

    /**
     * @throws MoonShineException
     */
    public function paginatorCast(mixed $data): ?PaginatorContract
    {
        if(! $data instanceof Paginator) {
            return null;
        }

        $paginator = new PaginatorCaster(
            $data->appends(
                moonshine()->getRequest()->getExcept('page')
            )->toArray(),
            $data->items()
        );

        return $paginator->cast();
    }
}
