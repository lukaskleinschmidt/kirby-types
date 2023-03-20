<?php

namespace LukasKleinschmidt\TypeHints;

use phpDocumentor\Reflection\DocBlock as Reflection;
use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlockFactory;
use Stringable;

class DocBlock implements Stringable
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
    ) {}

    public function render(): string
    {
        return snippet('docblock.stub', [
            'description' => $this->description,
            'summary'     => $this->summary,
            'tags'        => $this->tags,
        ], true);
    }

    public function serialize(int $indent = 0): string
    {
        $serializer = new Serializer($indent);

        return $serializer->getDocComment($this->toReflection());
    }

    public function toReflection(): Reflection
    {
        return DocBlockFactory::createInstance()->create($this->render());
    }

    public function __toString(): string
    {
        return $this->serialize();
    }
}
