<?php

namespace LukasKleinschmidt\Types;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Field;
use Kirby\Cms\File;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Cms\HasMethods;
use Kirby\Filesystem\F;
use LukasKleinschmidt\Types\Methods\BlueprintMethod;
use LukasKleinschmidt\Types\Methods\FieldMethod;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Types
{
	protected static $instance;

    protected array $methods = [];

    /**
     * Create a new Types instance.
     */
    public function __construct(
        protected App $app
    ) {}

    /**
     * Create a new Types instance.
     */
    public static function instance(App $app = null): static
    {
        return static::$instance ??= new static($app ?? App::instance());
    }

    /**
     * Returns the types content.
     */
    public function render(array $data): string
    {
        return snippet('stubs/types-template', $data, true);
    }

    /**
     * Create the types file.
     */
    public function create(string $filename = null): void
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
    public function pushMethod(Method $method, Closure|bool $overwrite = false): void
    {
        if ($method->exists()) {
            return;
        }

        $key = $this->getMethodKey($method);

        if ($exists = array_key_exists($key, $this->methods)) {
            $overwrite = is_callable($overwrite)
                ? $overwrite($this->methods[$key], $method)
                : $overwrite;

            if ($overwrite instanceof Method) {
                $method = $overwrite;
            }
        }

        if (! $exists || $overwrite) {
            $this->methods[$key] = $method;
        }
    }

    public function withBlueprintFields(): void
    {
        $this->addBlueprintMethods($site = $this->app->site());

        foreach ($this->app->blueprints('pages') as $name) {
            $this->addBlueprintMethods(Page::factory([
                'template' => $name,
                'model'    => $name,
                'slug'     => $name,
            ]), $name);
        }

        foreach ($this->app->blueprints('files') as $name) {
            $this->addBlueprintMethods(File::factory([
                'filename' => $name,
                'template' => $name,
                'parent'   => $site,
            ]), $name);
        }

        foreach ($this->app->blueprints('users') as $name) {
            $this->addBlueprintMethods(User::factory([
                'model' => $name,
            ]), $name);
        }
    }

    public function withClassMethods(): void
    {
        foreach (get_declared_classes() as $class) {
            if (class_uses_recursive($class)[HasMethods::class] ?? false) {
                $this->addClassMethods($class);
            }
        }
    }

    public function withFieldMethods(): void
    {
        $target = new ReflectionClass(Field::class);

        $this->addFieldMethods(
            $this->app->core()->fieldMethods(), $target
        );

        $this->addFieldMethodAliases(
            $this->app->core()->fieldMethodAliases(), $target
        );

        foreach ($this->app->plugins() as $plugin) {
            $extends = $plugin->extends();

            if (isset($extends['fieldMethods'])) {
                $this->addFieldMethods($extends['fieldMethods'], $target);
            }
        }
    }

    protected function addBlueprintMethods(ModelWithContent $model, string $name = null): void
    {
        $function = new ReflectionFunction(fn (): Field =>
            new Field($model, 'key', 'value')
        );

        $target = new ReflectionClass($model);

        foreach ($model->blueprint()->fields() as $field) {
            $method = new BlueprintMethod($function, $target, $field['name']);

            $method->document($field['type'], $name);

            $this->pushMethod($method, function (Method $a, Method $b) {
                if (
                    $a instanceof BlueprintMethod &&
                    $b instanceof BlueprintMethod
                ) {
                    $a->merge($b);
                }
            });
        }
    }

    public function addClassMethods(string $class): void
    {
        $target = new ReflectionClass($class);

        foreach ($class::$methods as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->pushMethod(
                new Method($function, $target, $name)
            );
        }
    }

    public function addFieldMethods(array $methods, ReflectionClass $target): void
    {
        foreach ($methods as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->pushMethod(
                new FieldMethod($function, $target, $name)
            );
        }
    }

    public function addFieldMethodAliases(array $aliases, ReflectionClass $target): void
    {
        foreach ($aliases as $name => $alias) {
            if ($function = $this->getMethod($alias, $target)) {
                $this->pushMethod(
                    new FieldMethod($function, $target, $name, $alias)
                );
            }
        }
    }

    public function getKey(ReflectionClass $target, string $name): string
    {
        return $target->getName() . '::' . strtolower($name);
    }

    public function getMethod(string $alias, ReflectionClass $target): ReflectionFunction|ReflectionMethod|null
    {
        if ($target->hasMethod($alias)) {
            return $target->getMethod($alias);
        }

        $key = $this->getKey($target, $alias);

        if (isset($this->methods[$key])) {
            return $this->methods[$key]->function();
        }

        return null;
    }

    public function getMethodKey(Method $method): string
    {
        return $this->getKey($method->target(), $method->getName());
    }
}
