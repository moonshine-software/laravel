<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\{outro, text};

#[AsCommand(name: 'moonshine:type-cast')]
class MakeTypeCastCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:type-cast {className?}';

    protected $description = 'Create type cast class';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $className = $this->argument('className') ?? text(
            'Class name',
            required: true
        );

        $path = $this->getDirectory() . "/TypeCasts/$className.php";

        if (! is_dir($this->getDirectory() . '/TypeCasts')) {
            $this->makeDir($this->getDirectory() . '/TypeCasts');
        }

        $this->copyStub('TypeCast', $path, [
            '{namespace}' => moonshineConfig()->getNamespace('\TypeCasts'),
            'DummyCast' => $className,
        ]);

        outro(
            "$className was created: " . str_replace(
                base_path(),
                '',
                $path
            )
        );

        return self::SUCCESS;
    }
}
