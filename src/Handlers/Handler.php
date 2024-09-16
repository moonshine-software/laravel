<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Handlers;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use MoonShine\Contracts\Core\CrudResourceContract;
use MoonShine\Contracts\Core\HasCoreContract;
use MoonShine\Contracts\Core\HasResourceContract;
use MoonShine\Contracts\Core\HasUriKeyContract;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\HasIconContract;
use MoonShine\Contracts\UI\HasLabelContract;
use MoonShine\Core\Traits\HasResource;
use MoonShine\Core\Traits\WithCore;
use MoonShine\Core\Traits\WithUriKey;
use MoonShine\Support\Traits\Makeable;
use MoonShine\Support\Traits\WithQueue;
use MoonShine\UI\Traits\WithIcon;
use MoonShine\UI\Traits\WithLabel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static static make(Closure|string $label)
 *
 * @implements HasResourceContract<CrudResourceContract>
 */
abstract class Handler implements HasIconContract, HasResourceContract, HasUriKeyContract, HasLabelContract, HasCoreContract
{
    use Makeable;
    use WithQueue;
    /**
     * @use HasResource<CrudResourceContract, CrudResourceContract>
     */
    use HasResource;
    use WithIcon;
    use WithUriKey;
    use WithLabel;
    use WithCore;
    use Conditionable;

    /** @var ?Closure(ActionButtonContract, static): ActionButtonContract  */
    protected ?Closure $modifyButton = null;

    protected array|Closure $notifyUsers = [];

    public function __construct(Closure|string $label)
    {
        $this->setLabel($label);
    }

    abstract public function handle(): Response;

    abstract public function getButton(): ActionButtonContract;

    public function getUrl(): string
    {
        return $this->getResource()?->getRoute('handler', query: ['handlerUri' => $this->getUriKey()]) ?? '';
    }

    /**
     * @param  Closure(ActionButtonContract $button, static $ctx): ActionButtonContract  $callback
     */
    public function modifyButton(Closure $callback): static
    {
        $this->modifyButton = $callback;

        return $this;
    }

    protected function prepareButton(ActionButtonContract $button): ActionButtonContract
    {
        if (! is_null($this->modifyButton)) {
            return call_user_func($this->modifyButton, $button, $this);
        }

        return $button;
    }

    /**
     * @param array|Closure(static $ctx): array $ids
     */
    public function notifyUsers(array|Closure $ids): static
    {
        $this->notifyUsers = $ids;

        return $this;
    }

    public function getNotifyUsers(): array
    {
        return value($this->notifyUsers, $this);
    }
}
