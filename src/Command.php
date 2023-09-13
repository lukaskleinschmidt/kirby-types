<?php

namespace LukasKleinschmidt\Types;

use Kirby\CLI\CLI;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use League\CLImate\CLImate;

class Command
{
    protected static $version = '2.0.1';

    protected array $options = [];

    protected Types $types;

    public function __construct(protected CLI $cli)
    {
        $kirby   = $cli->kirby();
        $options = $kirby->option('lukaskleinschmidt.types');

        foreach ($options as $key => $value) {
            $name = Str::kebab($key);

            if ($cli->climate()->arguments->defined($name)) {
                $value = $cli->arg($name);
            }

            $options[$key] = $value;
        }

        if ($options['include'] === true) {
            $input = $cli->climate()->checkboxes('select included parts', [
                'blueprints' => 'blueprint fields',
                'methods'    => 'custom methods',
                'decorators' => 'decorators',
                'aliases'    => 'aliases',
            ]);

            $options['include'] = $input->prompt();
        }

        $this->options = $options;
        $this->types   = new Types($kirby, $options);
    }

    public static function run(CLI $cli): Command
    {
        return (new static($cli))->handle();
    }

    public static function version(): string
    {
        return static::$version;
    }

    public function cli(): CLI
    {
        return $this->cli;
    }

    public function climate(): CLImate
    {
        return $this->cli->climate();
    }

    public function handle(): static
    {
        $this->climate()->line('<light_blue>kirby-types v' . static::version() . '</light_blue> <light_green>types:create</light_green>');

        return match ($this->collect()->write()) {
            null  => $this,
            true  => $this->success(),
            false => $this->error(),
        };
    }

    public function include(string $type): bool
    {
        return in_array($type, $this->option('include'));
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return A::get($this->options, $key, $default);
    }

    public function types(): Types
    {
        return $this->types;
    }

    protected function collect(): static
    {
        $types = $this->types();

        if ($this->include('blueprints')) {
            $this->climate()->line('<light_green>✓</light_green> added blueprints fields');
            $types->withBlueprints();
        }

        if ($this->include('methods')) {
            $this->climate()->line('<light_green>✓</light_green> added custom methods');
            $types->withFieldMethods();
            $types->withTraitMethods();
            $types->withValidators();
        }

        if ($this->include('decorators')) {
            $this->climate()->line('<light_green>✓</light_green> added decorators');
            $types->withConfigDecorators();
            $types->withOptionDecorators();
        }

        if ($this->include('aliases')) {
            $this->climate()->line('<light_green>✓</light_green> added aliases');
            $types->withAliases();
            $types->withConfigAliases();
            $types->withOptionAliases();
        }

        return $this;
    }

    protected function error(): static
    {
        $this->climate()->error('types helper could not be created');
        return $this;
    }

    protected function success(): static
    {
        $this->climate()->lightGreen('types helper created successfully');
        return $this;
    }

    protected function write(): ?bool
    {
        return $this->types()->create(fn (string $path) =>
            $this->writeConfirmation($path)
        );
    }

    protected function writeConfirmation(string $path): bool
    {
        $file = substr(strrchr($path, '/'), 1);
        $path = rtrim($path, $file);

        return $this->climate()
            ->confirm('do you want to overwrite <dim>' . $path . '</dim><light_blue>' . $file . '</light_blue>')
            ->defaultTo('y')
            ->confirmed();
    }
}
