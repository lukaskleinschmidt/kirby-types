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

    protected array $config;

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
            $namespace = $method->target()->getNamespaceName();
            $class     = $method->target()->getShortName();
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
        if ($method->exists() && $overwrite === false) {
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

    public function config(string $key = null, mixed $default = null): mixed
    {
        $this->config ??= require_once dirname(__DIR__) . '/config.php';

        if (! is_null($key)) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    public function withConfigMethods(): void
    {
        foreach ($this->config('methods', []) as $key => $value) {
            $this->addConfigMethods($value, new ReflectionClass($key));
        }
    }

    public function addConfigMethods(array $methods, ReflectionClass $target): void
    {
        foreach ($methods as $name => $callback) {
            $method = $this->getMethod($name, $target);

            if (is_null($method) && $target->hasMethod($name)) {
                $this->pushMethod($method = new Method($target->getMethod($name), $target), true);
            }

            if (! is_null($method)) {
                $callback($method);
            }
        }
    }

    public function withBlueprintFields(): void
    {
        $this->addBlueprintFields($site = $this->app->site());

        foreach ($this->app->blueprints('pages') as $name) {
            $this->addBlueprintFields(Page::factory([
                'template' => $name,
                'model'    => $name,
                'slug'     => $name,
            ]), $name);
        }

        foreach ($this->app->blueprints('files') as $name) {
            $this->addBlueprintFields(File::factory([
                'filename' => $name,
                'template' => $name,
                'parent'   => $site,
            ]), $name);
        }

        foreach ($this->app->blueprints('users') as $name) {
            $this->addBlueprintFields(User::factory([
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

    public function addBlueprintFields(ModelWithContent $model, string $name = null): void
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
            if ($reflection = $this->getMethodReflection($alias, $target)) {
                $this->pushMethod(
                    new FieldMethod($reflection, $target, $name, $alias)
                );
            }
        }
    }

    public function getKey(ReflectionClass $target, string $name): string
    {
        return $target->getName() . '::' . strtolower($name);
    }

    public function getMethod(string $name, ReflectionClass $target): Method|null
    {
        return $this->methods[$this->getKey($target, $name)] ?? null;
    }

    public function getMethodReflection(string $name, ReflectionClass $target): ReflectionFunction|ReflectionMethod|null
    {
        if ($target->hasMethod($name)) {
            return $target->getMethod($name);
        }

        return $this->getMethod($name, $target)?->function();
    }

    public function getMethodKey(Method $method): string
    {
        return $this->getKey($method->target(), $method->getName());
    }
}
