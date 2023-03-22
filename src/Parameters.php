<?php

namespace LukasKleinschmidt\Types;

use Exception;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use Stringable;

class Parameters extends Collection implements Stringable
{
    public static function from(ReflectionFunctionAbstract $function): static
    {
        return new static($function->getParameters());
    }

    public function __set(string $key, mixed $value): void
	{
        if ($value instanceof ReflectionParameter) {
            $value = new Parameter($value);
        }

        if (! $value instanceof Parameter) {
            throw new Exception('Unexpected parameter instance');
        }

		$this->data[$key] = $value;
	}

    public function withDefaults(bool $value = true): static
    {
        return $this->map(fn (Parameter $parameter) =>
            $parameter->withDefault($value)
        );
    }

    public function withTypes(bool $value = true): static
    {
        return $this->map(fn (Parameter $parameter) =>
            $parameter->withType($value)
        );
    }

    public function detailed(bool $value = true): static
    {
        return $this->map(fn (Parameter $parameter) =>
            $parameter->withType($value)->withDefault($value)
        );
    }

    public function __toString(): string
    {
        return join(', ', $this->data);
    }
}
