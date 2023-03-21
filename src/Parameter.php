<?php

namespace LukasKleinschmidt\Types;

use ReflectionParameter;
use Stringable;

class Parameter implements Stringable
{
    protected string $variableName;

    protected string $variable;

    protected ?string $default;

    protected ?string $type;

    protected bool $withDefault = false;

    protected bool $withType = false;

    public function __construct(
        protected ReflectionParameter $parameter
    ) {}

    public function getVariableName(): string
    {
        return $this->variableName ??= $this->parameter->getName();
    }

    public function getVariable(): string
    {
        return $this->variable ??= get_parameter_variable($this->parameter);
    }

    public function getDefault(): ?string
    {
        return $this->default ??= get_parameter_default($this->parameter);
    }

    public function getType(): ?string
    {
        return $this->type ??= get_parameter_type($this->parameter);
    }

    public function withDefault(bool $value = true): static
    {
        $this->withDefault = $value;

        return $this;
    }

    public function withType(bool $value = true): static
    {
        $this->withType = $value;

        return $this;
    }

    public function __toString(): string
    {
        $value = $this->getVariable();

        if ($this->withType && $type = $this->getType()) {
            $value = $type . ' ' . $value;
        }

        if ($this->withDefault && $default = $this->getDefault()) {
            $value .= ' = ' . $default;
        }

        $this->withDefault = false;
        $this->withType    = false;

        return $value;
    }
}
