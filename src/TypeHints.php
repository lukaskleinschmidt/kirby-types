<?php

namespace LukasKleinschmidt\TypeHints;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Cms\File;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Cms\HasMethods;
use Kirby\Filesystem\F;
use LukasKleinschmidt\TypeHints\BlueprintMethod;
use LukasKleinschmidt\TypeHints\FieldMethod;
use ReflectionClass;
use ReflectionFunction;

class TypeHints
{
	protected static $instance;

    protected array $methods = [];

    /**
     * Create a new TypeHints instance.
     */
    public function __construct(
        protected App $app
    ) {}

    /**
     * Create a new TypeHints instance.
     */
    public static function instance(App $app = null): static
    {
        return static::$instance ??= new static($app ?? App::instance());
    }

    /**
     * Returns the type hints content.
     */
    public function render(array $data): string
    {
        return snippet('typehints.stub', $data, true);
    }

    /**
     * Writes the type hints to file.
     */
    public function write(string $filename = null): void
    {
        if (empty($filename)) {
            $filename = 'types';
        }

        if (! str_ends_with($filename, '.php')) {
            $filename .= '.php';
        }

        $extensions = [];

        foreach ($this->methods as $method) {
            $namespace = $method->getTargetNamespace();
            $class     = $method->getTargetClass();
            $name      = $method->getName();

            $extensions[$namespace][$class][$name] = $method;
        }


        F::write($this->app->root('base') . '/' . $filename, $this->render([
            'extensions' => $extensions,
        ]));
    }

    /**
     * Push a new method to the collection.
     */
    public function methodsPush(Method $method, Closure|bool $overwrite = false): void
    {
        if ($method->exists()) {
            return;
        }

        $index = $method->index();

        if ($exists = array_key_exists($index, $this->methods)) {
            $overwrite = is_callable($overwrite)
                ? $overwrite($this->methods[$index], $method)
                : $overwrite;

            if ($overwrite instanceof Method) {
                $method = $overwrite;
            }
        }

        if (! $exists || $overwrite) {
            $this->methods[$index] = $method;
        }
    }

    public function blueprints(): void
    {
        $this->blueprintMethods($site = $this->app->site());

        foreach ($this->app->blueprints('pages') as $name) {
            $this->blueprintMethods(Page::factory([
                'template' => $name,
                'model'    => $name,
                'slug'     => $name,
            ]), $name);
        }

        foreach ($this->app->blueprints('files') as $name) {
            $this->blueprintMethods(File::factory([
                'filename' => $name,
                'template' => $name,
                'parent'   => $site,
            ]), $name);
        }

        foreach ($this->app->blueprints('users') as $name) {
            $this->blueprintMethods(User::factory([
                'model' => $name,
            ]), $name);
        }
    }

    public function blueprintMethods(ModelWithContent $model, string $name = null): void
    {
        $target   = new ReflectionClass($model);
        $function = new ReflectionFunction(fn (): Field =>
            new Field($model, 'key', 'value')
        );

        foreach ($model->blueprint()->fields() as $field) {
            $method = new BlueprintMethod($function, $target, $field['name']);

            $method->document($field['type'], $name);

            $this->methodsPush($method, function (Method $a, Method $b) {
                if (
                    $a instanceof BlueprintMethod &&
                    $b instanceof BlueprintMethod
                ) {
                    $a->merge($b);
                }
            });
        }
    }

    public function traits(): void
    {
        foreach (get_declared_classes() as $class) {
            if (class_uses_recursive($class)[HasMethods::class] ?? false) {
                $this->traitMethods($class);
            }
        }
    }

    public function traitMethods(string $class): void
    {
        $target = new ReflectionClass($class);

        foreach ($class::$methods as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->methodsPush(
                new Method($function, $target, $name)
            );
        }
    }

    public function fieldMethods(): void
    {
        $target = new ReflectionClass(Field::class);

        foreach ($this->app->core()->fieldMethods() as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->methodsPush(
                new FieldMethod($function, $target, $name)
            );
        }

        foreach (kirby()->plugins() as $plugin) {
            $fieldMethods = $plugin->extends()['fieldMethods'] ?? [];

            foreach ($fieldMethods as $name => $closure) {
                $function = new ReflectionFunction($closure);

                $this->methodsPush(
                    new FieldMethod($function, $target, $name)
                );
            }
        }

        foreach ($this->app->core()->fieldMethodAliases() as $name => $alias) {
            $index = $target->getName() . '::' . strtolower($alias);

            if ($target->hasMethod($alias)) {
                $function = $target->getMethod($alias);
            } else if (isset($this->methods[$index])) {
                $function = $this->methods[$index]->function();
            }

            if (! isset($function)) {
                continue;
            }

            $this->methodsPush(
                new FieldMethod($function, $target, $name, $alias)
            );
        }
    }
}
