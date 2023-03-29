<?php

namespace LukasKleinschmidt\Types\Methods;

use LukasKleinschmidt\Types\Method;

class StaticMethod extends Method
{
    /**
     * Checks whether the method should be called statically.
     */
    public function static(): bool
    {
        return true;
    }
}
