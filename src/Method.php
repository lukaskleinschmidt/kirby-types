<?php

namespace LukasKleinschmidt\Types;

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
     * The Docblock.
     */
    protected Comment $comment;

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
    protected Parameters $parameters;

    /**
     * Create a new instance.
     */
    public function __construct(
        ReflectionFunction|ReflectionMethod $function,
        ReflectionClass $target,
        string $name = null,
        string $alias = null
    ) {
        $this->function   = $function;
        $this->target     = $target;
        $this->alias      = $alias;
        $this->name       = $name ?? $function->getName();
        $this->comment    = $this->createComment();
        $this->parameters = $this->createParameters();
    }

    /**
     * Create the comment.
     */
    protected function createComment(): Comment
    {
        return Comment::from($this->function);
    }

    /**
     * Create the parameters.
     */
    protected function createParameters(): Parameters
    {
        return Parameters::from($this->function);
    }

    /**
     * Returns the comment.
     */
    public function comment(): Comment
    {
        return $this->comment;
    }

    /**
     * Returns the parameters.
     */
    public function parameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * Checks whether the method already exists on the target.
     */
    public function exists(): bool
    {
        return $this->target->hasMethod($this->name);
    }

    /**
     * Checks whether the method should be called statically.
     */
    public function static(): bool
    {
        return $this->function->isStatic();
    }

    /**
     * Returns the function.
     */
    public function function(): ReflectionFunction|ReflectionMethod
    {
        return $this->function;
    }

    /**
     * Returns the target.
     */
    public function target(): ReflectionClass
    {
        return $this->target;
    }

    public function hasComment(): bool
    {
        return $this->comment()->hasContent();
    }

    public function getComment(int $indent = 0): string
    {
        return preg_replace('/(\s+\*)+(\s+\*\/)$/', '$2',
            $this->comment()->serialize($indent)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getParams(): Parameters
    {
        return $this->parameters();
    }

    public function getReturnType(): string
    {
        if ($type = $this->function->getReturnType()) {
            return ': ' . reflection_type_value($type);
        }

        return '';
    }
}
