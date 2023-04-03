<?php

namespace LukasKleinschmidt\Types;

use LukasKleinschmidt\Types\Tags\ParamTag;
use LukasKleinschmidt\Types\Tags\ReturnTag;
use Stringable;

class Tag implements Stringable
{
    public function __construct(
        protected string $name,
        protected ?string $content = null,
    ) {}

    public static function make(string $name, string $content = null): static
    {
        $name = ltrim($name, '@');

        return match ($name) {
            'param'  => new ParamTag($name, $content),
            'return' => new ReturnTag($name, $content),
            default  => new static($name, $content),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content = null)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return trim('@' . $this->getName() . ' ' . $this->getContent());
    }
}
