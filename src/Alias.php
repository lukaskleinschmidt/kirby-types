<?php

namespace LukasKleinschmidt\Types;

use ReflectionClass;

class Alias
{
    protected ReflectionClass $target;

    protected string $name;

    protected ?string $namespace = null;

    public function __construct(ReflectionClass $target, string $name = null)
    {
        $this->target = $target;
        $this->name   = $name ??= $target->getShortName();

        if ($class = strrchr($name, '\\')) {
            $this->namespace = str_replace($class, '', $name);
            $this->name      = trim($class, '\\');
        }

        if ($this->name === strtolower($name = $target->getShortName())) {
            $this->name = $name;
        }
    }

    public function target(): ReflectionClass
    {
        return $this->target;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
