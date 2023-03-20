<?php

namespace LukasKleinschmidt\TypeHints;

use LukasKleinschmidt\TypeHints\Method;

class FieldMethod extends Method
{
    protected function normalize(): void
    {
        $docblock = $this->docblock();

        /**
         * @var \phpDocumentor\Reflection\DocBlock\Tags\Param $param
         */
        foreach ($docblock->getTagsByName('param') as $key => $param) {
            if (! $key && $param->getVariableName() === 'field') {
                $docblock->removeTag($param);
                break;
            }
        }

        foreach ($this->parameters() as $key => $param) {
            if (! $key && $param->getName() === 'field') {
                unset($this->parameters[$key]);
                break;
            }
        }
    }
}
