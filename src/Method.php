<?php

namespace LukasKleinschmidt\TypeHints;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Method
{
    /**
     * The function reflection.
     */
    protected ReflectionFunction|ReflectionMethod $function;

    /**
     * The reflection of the intended target.
     */
    protected ReflectionClass $target;

    /**
     * The function DocBlock.
     */
    protected DocBlock $docblock;

    /**
     * The function context.
     */
    protected Context $context;

    /**
     * The method alias.
     */
    protected ?string $alias;

    /**
     * The method name.
     */
    protected string $name;

    /**
     * The method parameters.
     */
    protected array $parameters;

    /**
     * Create a new instance.
     */
    public function __construct(
        ReflectionFunction|ReflectionMethod $function,
        ReflectionClass $target,
        string $name = null,
        string $alias = null
    ) {
        $this->function = $function;
        $this->target   = $target;
        $this->alias    = $alias;
        $this->name     = $name ?? $function->getName();

        if ($function instanceof ReflectionMethod) {
            $class = $function->getDeclaringClass();

            $this->context(
                $class->getNamespaceName(),
                $class->getFileName()
            );
        }

        $this->normalize();
    }

    /**
     * Normalize the method.
     */
    protected function normalize(): void
    {
        //
    }

    /**
     * Returns the docblock context.
     */
    protected function context(string $namespace = null, string $file = null): Context
    {
        if (isset($this->context)) {
            return $this->context;
        }

        $namespace ??= $this->function->getNamespaceName();
        $file      ??= $this->function->getFileName();

        return $this->context = (new ContextFactory)->createForNamespace(
            $namespace, file_get_contents($file)
        );
    }

    /**
     * Change the docblock context.
     */
    public function useContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * Returns the DocBlock.
     */
    protected function docblock(): DocBlock
    {
        return $this->docblock ??= DocBlockFactory::createInstance()->create(
            $this->function->getDocComment() ?: ' ', $this->context()
        );
    }

    /**
     * Returns the function parameters.
     *
     * @return \LukasKleinschmidt\TypeHints\Parameter[]
     */
    protected function parameters(): array
    {
        return $this->parameters ??= (function () {
            $parameters = [];

            foreach ($this->function->getParameters() as $param) {
                $parameters[] = new Parameter($param, $this->context());
            }

            return $parameters;
        })();
    }

    /**
     * Checks whether the method already exists on the target.
     */
    public function exists(): bool
    {
        return $this->target->hasMethod($this->name);
    }

    /**
     * Returns the method index.
     */
    public function index(): string
    {
        return $this->target->getName() . '::' . strtolower($this->name);
    }

    public function function(): ReflectionFunction|ReflectionMethod
    {
        return $this->function;
    }

    public function target(): ReflectionClass
    {
        return $this->target;
    }

    public function getDocComment(int $indent = 0): string
    {
        $serializer = new Serializer($indent, indentFirstLine: false);

        return $serializer->getDocComment($this->docblock());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getTargetClass(): string
    {
        return $this->target->getShortName();
    }

    public function getTargetNamespace(): string
    {
        return $this->target->getNamespaceName();
    }

    public function getParams(): string
    {
        return join(', ', $this->parameters());
    }

    public function getParamsWithTypes(): string
    {
        return join(', ', array_map(fn (Parameter $param) =>
            $param->withType(),
        $this->parameters()));
    }

    public function getParamsWithDefaults(): string
    {
        return join(', ', array_map(fn (Parameter $param) =>
            $param->withDefault(),
        $this->parameters()));
    }

    public function getParamsWithTypesAndDefaults(): string
    {
        return join(', ', array_map(fn (Parameter $param) =>
            $param->withType()->withDefault(),
        $this->parameters()));
    }

    public function getReturnType(): string
    {
        if ($type = $this->function->getReturnType()) {
            return ': ' . normalize_reflection_type($type);
        }

        return '';
    }
}
