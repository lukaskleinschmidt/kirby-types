<?php

namespace LukasKleinschmidt\Types;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Serializer;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionFunction;
use ReflectionMethod;
use Stringable;

class Comment implements Stringable
{
    public Tags $tags;

    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        Tags|array $tags = [],
        protected ?DocBlock $docBlock = null
    ) {
        if (! $tags instanceof Tags) {
           $tags = new Tags($tags);
        }

        $this->tags = $tags;
    }

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
        return new static(
            $docBlock->getSummary(),
            $docBlock->getDescription(),
            $docBlock->getTags(),
            $docBlock,
        );
    }

    public function docBlock(): ?DocBlock
    {
        return $this->docBlock;
    }

    public function hasContent(): bool
    {
        return $this->summary || $this->description || $this->tags->count();
    }

    public function render(): string
    {
        return snippet('stubs/types-comment', [
            'description' => $this->description,
            'summary'     => $this->summary,
            'tags'        => $this->tags->grouped(),
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

    public function mergeTags(array $tags): static
    {
        foreach ($tags as $name => $content) {
            if ($content instanceof Tag) {
                $this->tags->mergeTag($content);
                continue;
            }

            if (! is_string($name)) {
                $name = strstr($content, ' ', true);
            }

            $content = ltrim($content, $name);

            $this->tags->mergeTag(Tag::make(ltrim($name, '@'), trim($content)));
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->serialize();
    }
}
