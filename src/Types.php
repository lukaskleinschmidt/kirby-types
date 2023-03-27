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

    protected array $config;

    protected array $aliases = [];

    protected array $methods = [];

    /**
     * Create a new Types instance.
     */
    public function __construct(
        protected App $app,
        protected array $options = [],
    ) {}

    /**
     * Create a new Types instance.
     */
    public static function instance(App $app = null, array $options = []): static
    {
        return static::$instance ??= new static($app ?? App::instance(), $options);
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function config(string $key = null, mixed $default = null): mixed
    {
        $this->config ??= require_once dirname(__DIR__) . '/config.php';

        if (! is_null($key)) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    /**
     * Returns the types content.
     */
    public function render(): string
    {
        $methods = [];

        foreach ($this->methods as $method) {
            $namespace = $method->target()->getNamespaceName();
            $class     = $method->target()->getShortName();
            $name      = $method->getName();

            $methods[$namespace][$class][$name] = $method;
        }

        $aliases = [];

        foreach ($this->aliases as $alias) {
            $namespace = $alias->getNamespace();
            $name      = $alias->getName();

            if ($namespace) {
                continue;
            }

            $aliases[$namespace][$name] = $alias;
        }

        return snippet('stubs/types-template', [
            'methods' => $methods,
            'aliases' => $aliases,
        ], true);
    }

    /**
     * Create the types file.
     */
    public function create(string $filename = null, mixed $overwrite = null): mixed
    {
        if (empty($filename)) {
            $filename = $this->option('filename');
        }

        if (! str_ends_with($filename, '.php')) {
            $filename .= '.php';
        }

        $path = $this->app->root('base') . '/' . $filename;


        if (file_exists($path) && ! ($this->option('force') || value($overwrite, $path))) {
            return null;
        }

        return F::write($path, $this->render()) ? $path : false;
    }

    /**
     * Push a new alias to the collection.
     */
    public function pushAlias(Alias $alias, bool $overwrite = false): void
    {
        $key = $this->getAliasKey($alias);

        if (array_key_exists($key, $this->aliases) && $overwrite === false) {
            return;
        }

        $this->aliases[$key] = $alias;
    }

    /**
     * Push a new method to the collection.
     */
    public function pushMethod(Method $method, Closure|bool $overwrite = false): void
    {
        $args = [$method];
        $key  = $this->getMethodKey($method);

        if ($exists = array_key_exists($key, $this->methods)) {
            array_push($args, $this->methods[$key]);
        }

        $overwrite = value($overwrite, ...$args);
        $exists    = $method->exists() || $exists;

        if ($overwrite instanceof Method) {
            $method    = $overwrite;
            $overwrite = true;
        }

        if ($exists === true && $overwrite !== true) {
            return;
        }

        $this->methods[$key] = $method;
    }

    public function withAliases(): void
    {
        $aliases = require $this->app->root('kirby') . '/config/aliases.php';

        $this->addAliases($aliases);
    }

    public function withConfigAliases(): void
    {
        $this->addAliases($this->config('aliases', []));
    }

    public function withOptionAliases(): void
    {
        $this->addAliases($this->option('aliases', []));
    }

    public function withBlueprints(): void
    {
        $this->addBlueprints($site = $this->app->site());

        foreach ($this->app->blueprints('pages') as $name) {
            $this->addBlueprints(Page::factory([
                'template' => $name,
                'model'    => $name,
                'slug'     => $name,
            ]), $name);
        }

        foreach ($this->app->blueprints('files') as $name) {
            $this->addBlueprints(File::factory([
                'filename' => $name,
                'template' => $name,
                'parent'   => $site,
            ]), $name);
        }

        foreach ($this->app->blueprints('users') as $name) {
            $this->addBlueprints(User::factory([
                'model' => $name,
            ]), $name);
        }
    }

    public function withConfigDecorators(): void
    {
        foreach ($this->config('decorators', []) as $key => $value) {
            $this->addDecorator($value, new ReflectionClass($key));
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

    public function withOptionDecorators(): void
    {
        foreach ($this->option('decorators', []) as $key => $value) {
            $this->addDecorator($value, new ReflectionClass($key));
        }
    }

    public function withTraitMethods(): void
    {
        foreach (get_declared_classes() as $class) {
            if (class_uses_recursive($class)[HasMethods::class] ?? false) {
                $this->addTraitMethods($class);
            }
        }
    }

    public function addAliases(array $aliases): void
    {
        foreach ($aliases as $name => $class) {
            $class = new ReflectionClass($class);

            $this->pushAlias(new Alias($class, $name));
        }
    }

    public function addBlueprints(ModelWithContent $model, string $blueprint = null): void
    {
        $function = new ReflectionFunction(fn (): Field =>
            new Field($model, 'key', 'value')
        );

        $target = new ReflectionClass($model);

        foreach ($model->blueprint()->fields() as $field) {
            $method = new BlueprintMethod($function, $target, $field['name']);

            $method->document($field['type'], $blueprint);

            $this->pushMethod($method, function (Method $a, Method $b = null) {
                if ($a instanceof BlueprintMethod && $b instanceof BlueprintMethod) {
                    $b->merge($a);
                }
            });
        }
    }

    public function addDecorator(array $methods, ReflectionClass $target): void
    {
        foreach ($methods as $name => $callback) {
            $method = $this->getMethod($name, $target);

            if (is_null($method) && $target->hasMethod($name)) {
                $method = new Method($target->getMethod($name), $target);

                $this->pushMethod($method, true);
            }

            if ($method instanceof Method) {
                $callback($method);
            }
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

    public function addTraitMethods(string $class): void
    {
        $target = new ReflectionClass($class);

        foreach ($class::$methods as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->pushMethod(
                new Method($function, $target, $name)
            );
        }
    }

    public function getAliasKey(Alias $alias): string
    {
        if ($namespace = $alias->getNamespace()) {
            return strtolower($namespace . '\\' . $alias->getName());
        }

        return strtolower($alias->getName());
    }

    public function getMethod(string $name, ReflectionClass $target): Method|null
    {
        return $this->methods[$this->getMethodReflectionKey($name, $target)] ?? null;
    }

    public function getMethodKey(Method $method): string
    {
        return $this->getMethodReflectionKey($method->getName(), $method->target());
    }

    public function getMethodReflection(string $name, ReflectionClass $target): ReflectionFunction|ReflectionMethod|null
    {
        if ($target->hasMethod($name)) {
            return $target->getMethod($name);
        }

        return $this->getMethod($name, $target)?->function();
    }

    public function getMethodReflectionKey(string $name, ReflectionClass $target): string
    {
        return strtolower($target->getName() . '::' . $name);
    }
}
