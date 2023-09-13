<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\Blueprint;
use ReflectionClass;

class Fieldset
{
    public function __construct(
        protected array $fields,
        protected ReflectionClass|string $target
    ) {}

    public function fields(): array
    {
        return array_map(fn ($field) =>
            Blueprint::extend($field)
        , $this->fields);
    }

    public function target(): ReflectionClass
    {
        if ($this->target instanceof ReflectionClass) {
            return $this->target;
        }

        return new ReflectionClass($this->target);
    }
}
