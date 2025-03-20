<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\Blueprint;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Form\Field;
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
    return is_callable($value) ? $value(...$args): $value;
}

function reflection_type_value(ReflectionType $type): string
{
    if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
        $name = $type->getName();
        return str_replace($name, '\\' . ltrim($name, '\\'), $type);
    }

    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
        $types = array_map(__NAMESPACE__ . '\reflection_type_value', $type->getTypes());
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
            $types[$key] = join('', array_map(__NAMESPACE__ . '\type', $type));
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

function extract_fields(array $array, string|array $key): array
{
    $keys = (array) $key;
    $fields = [];

    foreach ($keys as $key) {
        extract_recursive($array, explode('.', $key), $fields);
    }

    $fields = array_map(function (array|string $field) {
        return Blueprint::extend($field);
    }, $fields);

    return array_filter($fields);
}

function extract_recursive(array $array, array $parts, array &$result): void
{
    $part = array_shift($parts);

    foreach ($array as $key => $value) {
        if (! pattern($part, $key)) {
            continue;
        }

        if (empty($parts)) {
            $result = array_merge($result, (array) $array[$part]);
        } else {
            extract_recursive($value, $parts, $result);
        }
    }
}

function pattern(string|array $pattern, string $value, bool $ignoreCase = false): bool
{
    if (! is_iterable($pattern)) {
        $pattern = [$pattern];
    }

    foreach ($pattern as $pattern) {
        $pattern = (string) $pattern;

        if ($pattern === $value) {
            return true;
        }

        if ($ignoreCase && mb_strtolower($pattern) === mb_strtolower($value)) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        if (preg_match('#^'.$pattern.'\z#'.($ignoreCase ? 'iu' : 'u'), $value) === 1) {
            return true;
        }
    }

    return false;
}

function field_saveable(string $type): bool
{
    try {
        return Field::factory($type)->isSaveable();
    } catch (InvalidArgumentException) {
        return false;
    }
}
