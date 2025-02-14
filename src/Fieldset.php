<?php

namespace LukasKleinschmidt\Types;

use Closure;
use Kirby\Cms\Blueprint;
use ReflectionClass;

class Fieldset
{
    public function __construct(
        protected array $fields,
        protected ReflectionClass|string $target
    ) {}

    /**
     * @return \Closure(array $field): \LukasKleinschmidt\Types\Fieldset
     */
    public static function factory(
        ReflectionClass|string $target,
        string|array $extract = 'fields'
    ): Closure {
        return function (array $field) use ($target, $extract) {
            return new static(extract_fields($field, $extract), $target);
        };
    }

    public function fields(): array
    {
        $fields = array_map(function (array|string $field) {
            return Blueprint::extend($field);
        }, $this->fields);

        return array_filter($fields);
    }

    public function target(): ReflectionClass
    {
        if ($this->target instanceof ReflectionClass) {
            return $this->target;
        }

        return new ReflectionClass($this->target);
    }
}
