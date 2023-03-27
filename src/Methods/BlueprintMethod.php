<?php

namespace LukasKleinschmidt\Types\Methods;

use LukasKleinschmidt\Types\Comment;
use LukasKleinschmidt\Types\Method;

class BlueprintMethod extends Method
{
    public static array $links = [
        'blocks',
        'checkboxes',
        'date',
        'email',
        'files',
        'gap',
        'headline',
        'hidden',
        'info',
        'layout',
        'line',
        'list',
        'multiselect',
        'number',
        'object',
        'pages',
        'radio',
        'range',
        'select',
        'slug',
        'structure',
        'tags',
        'tel',
        'text',
        'textarea',
        'time',
        'toggle',
        'toggles',
        'url',
        'users',
        'writer',
    ];

    public function createComment(): Comment
    {
        return new Comment('Returns the ' . $this->getName() . ' field.');
    }

    public function link(string $type): ?string
    {
        if (in_array($type, static::$links)) {
            return 'https://getkirby.com/docs/reference/panel/fields/' . $type;
        }

        return static::$links[$type] ?? null;
    }

    public function document(string $type, string $blueprint = null): static
    {
        $comment = $this->comment();

        $comment->description = $blueprint
            ? 'Uses a `' . $type . '` field in the `' . $blueprint . '` blueprint.'
            : 'Uses a `' . $type . '` field.';

        if ($link = $this->link($type)) {
            $comment->tags->add('see', $link);
        }

        return $this;
    }

    public function merge(BlueprintMethod $method): static
    {
        $comment = $this->comment();

        if ($description = $method->comment()->description) {
            $comment->description .= '\\' . PHP_EOL . $description;
        }

        $comment->tags->merge($method->comment()->tags);
        $comment->tags->unique();

        return $this;
    }
}
