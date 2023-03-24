<?php

namespace LukasKleinschmidt\Types;

use Closure;
use Exception;
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

    public function add(string $name, string $content = null): static
    {
        return $this->append(Tag::make($name, $content));
    }

    public function setContent(string $name, Closure|string $content): static
    {
        $name = ltrim($name, '@');

        foreach ($this->filter('getName', $name) as $key => $tag) {
            $tag->setContent(value($content, $tag, $key));
        }

        return $this;
    }

    public function merge(Tags $tags): static
    {
        return $this->append(...$tags->toArray());
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
}
