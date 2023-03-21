<?php

namespace LukasKleinschmidt\Types\Methods;

use LukasKleinschmidt\Types\Comment;
use LukasKleinschmidt\Types\Method;
use LukasKleinschmidt\Types\Parameters;

class FieldMethod extends Method
{
    protected function createComment(): Comment
    {
        $comment = parent::createComment();

        if ($docBlock = $comment->docBlock()) {
            /**
             * @var \phpDocumentor\Reflection\DocBlock\Tags\Param $param
             */
            foreach ($docBlock->getTagsByName('param') as $param) {
                if ($param->getVariableName() === 'field') {
                    $docBlock->removeTag($param);
                }
            }

            return Comment::fromDocBlock($docBlock);
        }

        return $comment;
    }

    protected function createParameters(): Parameters
    {
        $parameters = parent::createParameters();

        if ($parameters->get(0)?->getVariableName() === 'field') {
            $parameters->remove(0);
        }

        return $parameters;
    }
}
