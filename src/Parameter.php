<?php

namespace LukasKleinschmidt\TypeHints;

use phpDocumentor\Reflection\Types\Context;
use ReflectionParameter;
use Stringable;

class Parameter implements Stringable
{
    protected ReflectionParameter $parameter;

    protected Context $context;

    protected ?string $default;

    protected ?string $type;

    protected string $variable;

    protected bool $withDefault = false;

    protected bool $withType = false;

    public function __construct(ReflectionParameter $parameter, Context $context = null)
    {
        $this->parameter = $parameter;
        $this->context   = $context;

        $this->type     = $this->getType();
        $this->variable = $this->getVariable();
        $this->default  = $this->getDefault();
    }

    public function getName(): string
    {
        return $this->parameter->getName();
    }

    protected function getDefault(): ?string
    {
        if (! $this->parameter->isDefaultValueAvailable()) {
            return null;
        }

        $value = $this->parameter->getDefaultValue();

        if (is_bool($value)) {
            return $this->default = $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return $this->default = '[]';
        }

        if (is_null($value)) {
            return $this->default = 'null';
        }

        if (is_int($value)) {
            return $this->default = $value;
        }

        return var_export($value, true);
    }

    protected function getType(): ?string
    {
        if ($type = $this->parameter->getType()) {
            return normalize_reflection_type($type);
        }

        return null;
    }

    protected function getVariable(): string
    {
        return ($this->parameter->isPassedByReference() ? '&' : '') .
               ($this->parameter->isVariadic() ? '...' : '') .
               '$' . $this->getName();
    }

    public function hasDefault(): bool
    {
        return (bool) $this->default;
    }

    public function hasType(): bool
    {
        return (bool) $this->type;
    }

    public function withDefault(): static
    {
        $this->withDefault = true;

        return $this;
    }

    public function withType(): static
    {
        $this->withType = true;

        return $this;
    }

    public function __toString(): string
    {
        $value = $this->variable;

        if ($this->withType && $this->hasType()) {
            $value = $this->type . ' ' . $value;
        }

        if ($this->withDefault && $this->hasDefault()) {
            $value .= ' = ' . $this->default;
        }

        $this->withDefault = false;
        $this->withType = false;

        return $value;
    }
}
