<?php

if (! function_exists('class_uses_recursive')) {
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
}

if (! function_exists('trait_uses_recursive')) {
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
}

if (! function_exists('normalize_reflection_type')) {
    /**
     * Returns all traits used by a trait and its traits.
     */
    function normalize_reflection_type(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $name = $type->getName();
            return str_replace($name, '\\' . trim($name, '\\'), $type);
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = array_map('normalize_reflection_type', $type->getTypes());
            return str_replace($type->getTypes(), $types, $type);
        }

        return (string) $type;
    }
}
