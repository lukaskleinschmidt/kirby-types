<?php

use Kirby\Cms\App;
use Kirby\CLI\CLI;
use LukasKleinschmidt\TypeHints\TypeHints;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

App::plugin('lukaskleinschmidt/typehints', [
    'snippets' => [
        'docblock.stub'  => __DIR__ . '/snippets/docblock.stub.php',
        'typehints.stub' => __DIR__ . '/snippets/typehints.stub.php',
    ],
    'commands' => [
        'typehints:create' => [
            'description' => 'Create typehints',
            'command' => function (CLI $cli) {
                $typehints = TypeHints::instance($cli->kirby());

                $typehints->fieldMethods();
                $typehints->blueprints();
                $typehints->traits();

                $typehints->write($cli->arg('filename'));
            },
            'args' => [
                'filename' => [
                    'prefix' => 'f',
                    'longPrefix' => 'filename',
                    'description' => 'The name of the type hints file',
                ],
            ],
        ],
    ],
]);
