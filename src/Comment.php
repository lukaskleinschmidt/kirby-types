<?php

namespace LukasKleinschmidt\Types;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionFunction;
use ReflectionMethod;
use Stringable;

class Comment implements Stringable
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
        protected ?DocBlock $docBlock = null
    ) {}

    public static function from(ReflectionFunction|ReflectionMethod $source): static
    {
        $comment = $source->getDocComment();

        if (! $comment) {
            return new static;
        }

        if ($source instanceof ReflectionMethod) {
            $source = $source->getDeclaringClass();
        }

        $namespace = $source->getNamespaceName();
        $filename  = $source->getFileName();

        $context = (new ContextFactory)->createForNamespace(
            $namespace, file_get_contents($filename)
        );

        return static::fromString($comment, $context);
    }

    public static function fromString(string $comment, Context $context = null): static
    {
        $docBlock = DocBlockFactory::createInstance()->create(
            $comment, $context
        );

        return static::fromDocBlock($docBlock);
    }

    public static function fromDocBlock(DocBlock $docBlock): static
    {
        $tags = array_map(fn (Tag $type) =>
            '@' . $type->getName() . ' ' . $type
        , $docBlock->getTags());

        return new static(
            $docBlock->getSummary(),
            $docBlock->getDescription(),
            $tags,
            $docBlock,
        );
    }

    public function docBlock(): ?DocBlock
    {
        return $this->docBlock;
    }

    public function render(): string
    {
        return snippet('stubs/types-comment', [
            'description' => $this->description,
            'summary'     => $this->summary,
            'tags'        => $this->tags,
        ], true);
    }

    public function serialize(int $indent = 0): string
    {
        $serializer = new Serializer($indent, indentFirstLine: false);

        return $serializer->getDocComment($this->toDocBlock());
    }

    public function toDocBlock(): DocBlock
    {
        return DocBlockFactory::createInstance()->create(
            $this->render(), $this->docBlock()?->getContext()
        );
    }

    public function __toString(): string
    {
        return $this->serialize();
    }
}
