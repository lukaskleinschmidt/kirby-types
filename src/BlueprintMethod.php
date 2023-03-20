<?php

namespace LukasKleinschmidt\TypeHints;

use LukasKleinschmidt\TypeHints\DocBlock;
use LukasKleinschmidt\TypeHints\Method;
use phpDocumentor\Reflection\DocBlock as Reflection;

class BlueprintMethod extends Method
{
    public DocBlock $customDocblock;

    public array $links = [
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

    public function customDocblock(): DocBlock
    {
        return $this->customDocblock ??= new DocBlock(
            'Returns the ' . $this->getName() . ' field.',
        );
    }

    public function getLink(string $type): ?string
    {
        if (in_array($type, $this->links)) {
            return 'https://getkirby.com/docs/reference/panel/fields/' . $type;
        }

        return $this->links[$type] ?? null;
    }

    public function document(string $type, string $blueprint = null): static
    {
        $docblock = $this->customDocblock();

        $docblock->description = $blueprint
            ? 'Uses a `' . $type . '` field in the `' . $blueprint . '` blueprint.'
            : 'Uses a `' . $type . '` field.';

        if ($link = $this->getLink($type)) {
            $docblock->tags[] = '@see ' . $link;
        }

        return $this;
    }

    public function merge(BlueprintMethod $method): static
    {
        $docblock = $this->customDocblock();

        if ($description = $method->customDocblock()->description) {
            $docblock->description .= '\\' . PHP_EOL . $description;
        }

        $tags = $method->customDocblock()->tags;
        $tags = array_merge($docblock->tags, $tags);
        $tags = array_unique($tags);

        $docblock->tags = $tags;

        return $this;
    }

    protected function docblock(): Reflection
    {
        return $this->customDocblock()->toReflection();
    }
}
