<?php

namespace LukasKleinschmidt\Types;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\User;
use Kirby\Cms\HasMethods;
use Kirby\Content\Field;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Kirby\Toolkit\V;
use LukasKleinschmidt\Types\Methods\BlueprintMethod;
use LukasKleinschmidt\Types\Methods\FieldMethod;
use LukasKleinschmidt\Types\Methods\StaticMethod;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Types
{
    protected array $config;

    protected array $aliases = [];

    protected array $methods = [];

    /**
     * Create a new Types instance.
     */
    public function __construct(
        protected App $app,
        protected array $options = [],
    ) {
        $this->config = require_once dirname(__DIR__) . '/config.php';
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return A::get($this->options, $key, $default);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return A::get($this->config, $key, $default);
    }

    public function fieldset(string $path, array $field): ?Fieldset
    {
        $type = 'fieldsets.' . substr(strrchr($path, '.'), 1);
        $path = 'fieldsets.' . $path;

        $value   = $this->option($path) ?? $this->config($path);
        $value ??= $this->option($type) ?? $this->config($type);

        $value = value($value, $field);

        if (is_string($value)) {
            $value = ['fields', $value];
        }

        if (is_array($value)) {
            if ($fields = A::get($field, $value[0])) {
                $value = new Fieldset($fields, $value[1]);
            }
        }

        if ($value instanceof Fieldset) {
            return $value;
        }

        return null;
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
     * Returns the file path.
     */
    public function path(): string
    {
        $filename = $this->option('filename');

        if (! str_ends_with($filename, '.php')) {
            $filename .= '.php';
        }

        $base = $this->app->root('base') ?? $this->app->root('index');

        return $base . '/' . $filename;
    }

    /**
     * Create the types file.
     */
    public function create(mixed $overwrite = null): ?bool
    {
        $path = $this->path();

        if (file_exists($path) && ! ($this->option('force') || value($overwrite, $path))) {
            return null;
        }

        return F::write($path, $this->render());
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
        $this->addBlueprint($site = $this->app->site());

        foreach ($this->app->blueprints('pages') as $name) {
            $this->addBlueprint(Page::factory([
                'template' => $name,
                'model'    => $name,
                'slug'     => $name,
            ]));
        }

        foreach ($this->app->blueprints('files') as $name) {
            $this->addBlueprint(File::factory([
                'filename' => $name,
                'template' => $name,
                'parent'   => $site,
            ]));
        }

        foreach ($this->app->blueprints('users') as $name) {
            $this->addBlueprint(User::factory([
                'model' => $name,
            ]), 'users/' . $name);
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

    public function withValidators(): void
    {
        $target = new ReflectionClass(V::class);

        foreach (V::$validators as $name => $closure) {
            $function = new ReflectionFunction($closure);

            $this->pushMethod(new StaticMethod($function, $target, $name));
        }
    }

    public function addAliases(array $aliases): void
    {
        foreach ($aliases as $name => $class) {
            $class = new ReflectionClass($class);

            $this->pushAlias(new Alias($class, $name));
        }
    }

    public function addBlueprint(ModelWithContent $model, string $name = null): void
    {
        $target = new ReflectionClass($model);

        $blueprint = $model->blueprint();
        $fields    = $blueprint->fields();
        $name      = strtolower($name ?? $blueprint->name());

        $this->addBlueprintFields($name, $fields, $target);
    }

    public function addBlueprintFields(string $blueprint, array $fields, ReflectionClass $target): void
    {
        $function = new ReflectionFunction(fn (): Field =>
            new Field(null, 'key', 'value')
        );

        foreach ($fields as $name => $field) {
            if ($field === true) {
                $field = ['type' => $name];
            }

            $method = new BlueprintMethod($function, $target, $name);

            $method->document($type = $field['type'], $blueprint);

            $this->pushMethod($method, function (Method $a, Method $b = null) {
                if ($a instanceof BlueprintMethod && $b instanceof BlueprintMethod) {
                    $b->merge($a);
                }
            });

            $name = trim($blueprint . '.' . $name, '.');
            $path = trim($name . '.' . $type, '.');

            if ($fieldset = $this->fieldset($path, $field)) {
                $this->addBlueprintFields($name, $fieldset->fields(), $fieldset->target());
            }
        }
    }

    public function addDecorator(array $decorators, ReflectionClass $target): void
    {
        foreach ($decorators as $key => $value) {
            $method = $this->getMethod($key, $target);

            if (is_null($method) && $target->hasMethod($key)) {
                $method = new Method($target->getMethod($key), $target);

                $this->pushMethod($method, true);
            }

            if (! $method instanceof Method) {
                continue;
            }

            $this->applyDecorator($method, $value);
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

    protected function applyDecorator(Method $method, Closure|array $decorator): void
    {
        if (is_array($decorator)) {
            $method->comment()->mergeTags($decorator);
        } elseif ($decorator instanceof Closure) {
            $decorator($method);
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
