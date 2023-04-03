<?php

namespace LukasKleinschmidt\Types;

use Closure;
use Exception;
use LukasKleinschmidt\Types\Tags\ParamTag;
use LukasKleinschmidt\Types\Tags\ReturnTag;
use phpDocumentor\Reflection\DocBlock\Tag as Reflection;

class Tags extends Collection
{
    public function __set(string $key, mixed $value): void
	{
        if ($value instanceof Reflection) {
            $value = Tag::make($value->getName(), $value);
        }

        if (! $value instanceof Tag) {
            throw new Exception('Unexpected tag instance');
        }

		$this->data[$key] = $value;
	}

    public function add(string $tag, string $content = null): static
    {
        return $this->append(Tag::make($tag, $content));
    }

    public function addTag(Tag $tag): static
    {
        return $this->append($tag);
    }

    public function grouped(): array
    {
        $values = [];

        foreach ($this->groupBy('getName') as $tags) {
            $values = array_merge($values, $tags->toArray());
        }

        return $values;
    }

    public function merge(Tags $tags): static
    {
        foreach ($tags as $tag) {
            $this->mergeTag($tag);
        }

        return $this;
    }

    public function mergeParam(ParamTag $tag): static
    {
        $variable = $tag->getVariable();

        if (is_null($variable)) {
            return $this->addTag($tag);
        }

        return $this->updateOrAdd($tag, fn (Tag $tag) =>
            str_contains($tag, $variable)
        );
    }

    public function mergeReturn(ReturnTag $tag): static
    {
        return $this->updateOrAdd($tag, fn (Tag $tag) =>
            str_starts_with($tag, '@return')
        );
    }

    public function mergeTag(Tag $tag): static
    {
        if (method_exists($this, $method = 'merge' . $tag->getName())) {
            return $this->{$method}($tag);
        }

        return $this->addTag($tag);
    }

    public function unique(): static
    {
        $values = [];

        foreach ($this->data as $key => $value) {
            $value = strval($value);

            if (! in_array($value, $values, true)) {
                $values[] = $value;
            } else {
                $this->remove($key);
            }
        }

        return $this;
    }

    public function updateOrAdd(Tag $tag, Closure $callback): static
    {
        if ($match = $this->filter($callback)->first()) {
            $match->setContent($tag->getContent());
        } else {
            $this->addTag($tag);
        }

        return $this;
    }
}
