<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\App;
use Kirby\CLI\CLI;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

App::plugin('lukaskleinschmidt/types', [
    'snippets' => [
        'stubs/types-comment'  => __DIR__ . '/snippets/comment.stub.php',
        'stubs/types-template' => __DIR__ . '/snippets/template.stub.php',
    ],
    'commands' => [
        'types:create' => [
            'description' => 'Create a new IDE helper file',
            'command' => function (CLI $cli) {
                $typehints = Types::instance($cli->kirby());

                $typehints->withFieldMethods();
                $typehints->withClassMethods();
                $typehints->withBlueprintFields();
                $typehints->withConfigMethods();

                $typehints->create($cli->arg('filename'));
            },
            'args' => [
                'filename' => [
                    'prefix' => 'f',
                    'longPrefix' => 'filename',
                    'description' => 'The path to the helper file',
                ],
            ],
        ],
    ],
]);
