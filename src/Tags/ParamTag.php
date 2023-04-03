<?php

namespace LukasKleinschmidt\Types\Tags;

use LukasKleinschmidt\Types\Tag;

class ParamTag extends Tag
{
    public function getVariable(): ?string
    {
        if (! preg_match('/(\$[^\s]+)/', $this->content, $matches)) {
            return null;
        }

        return $matches[0];
    }
}
