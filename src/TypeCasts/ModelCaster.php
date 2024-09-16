<?php

declare(strict_types=1);

namespace MoonShine\Laravel\TypeCasts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\Paginator\PaginatorContract;
use MoonShine\Contracts\Core\TypeCasts\DataCasterContract;

/**
 * @template  T of Model
 *
 * @implements DataCasterContract<T>
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
     * @return ModelDataWrapper<T>
     */
    public function cast(mixed $data): ModelDataWrapper
    {
        /** @phpstan-ignore-next-line  */
        if (is_array($data)) {
            /** @var T $model */
            $model = new ($this->getClass());
            $data = $model->forceFill($data);
            $data->exists = ! empty($data->getKey());
        }

        return new ModelDataWrapper($data);
    }

    public function paginatorCast(mixed $data): ?PaginatorContract
    {
        if (! $data instanceof Paginator && ! $data instanceof CursorPaginator) {
            return null;
        }

        /**
         * @phpstan-var (Paginator|CursorPaginator)&Arrayable $data
         */
        $paginator = new PaginatorCaster(
            $data->appends(
                moonshine()->getRequest()->getExcept('page')
            )->toArray(),
            $data->items()
        );

        return $paginator->cast();
    }
}
