<?php

namespace LukasKleinschmidt\Types;

use Closure;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

/**
 * Returns all traits used by a class, its parent classes and trait of their traits.
 */
function class_uses_recursive(object|string $class): array
{
    if (is_object($class)) {
        $class = get_class($class);
    }

    $results = [];

    foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
        $results += trait_uses_recursive($class);
    }

    return array_unique($results);
}

/**
 * Returns all traits used by a trait and its traits.
 */
function trait_uses_recursive(string $trait): array
{
    $traits = class_uses($trait) ?: [];

    foreach ($traits as $trait) {
        $traits += trait_uses_recursive($trait);
    }

    return $traits;
}

/**
 * Return the default value of the given value.
 */
function value(mixed $value, ...$args): mixed
{
    return $value instanceof Closure ? $value(...$args): $value;
}

function reflection_type_value(ReflectionType $type): string
{
    if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
        $name = $type->getName();
        return str_replace($name, '\\' . ltrim($name, '\\'), $type);
    }

    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
        $types = array_map(reflection_type_value::class, $type->getTypes());
        return str_replace($type->getTypes(), $types, $type);
    }

    return (string) $type;
}

function get_parameter_type(ReflectionParameter $parameter): ?string
{
    if ($type = $parameter->getType()) {
        return reflection_type_value($type);
    }

    return null;
}

function get_parameter_variable(ReflectionParameter $parameter): string
{
    return join('', [
        $parameter->isPassedByReference() ? '&' : '',
        $parameter->isVariadic() ? '...' : '',
        '$',
        $parameter->getName(),
    ]);
}

function get_parameter_default(ReflectionParameter $parameter): ?string
{
    if (! $parameter->isDefaultValueAvailable()) {
        return null;
    }

    $value = $parameter->getDefaultValue();

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        return '[]';
    }

    if (is_null($value)) {
        return 'null';
    }

    if (is_int($value)) {
        return $value;
    }

    return var_export($value, true);
}

function type(string $type, string ...$args): string
{
    if (class_exists($type)) {
        $type = '\\' . ltrim($type, '\\');
    }

    return join('', [$type, ...$args]);
}

function types(string $glue, array $types): string
{
    foreach ($types as $key => $type) {
        if (is_array($type)) {
            $types[$key] = join('', array_map(type::class, $type));
        } else {
            $types[$key] = type($type);
        }
    }

    return join($glue, $types);
}

/**
 * @param string|string[] ...$types
 */
function union_type(string|array ...$types): string
{
    return types('|', $types);
}

/**
 * @param string|string[] ...$types
 */
function intersection_type(string|array ...$types): string
{
    return types('&', $types);
}
