<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\App;
use Kirby\CLI\CLI;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

App::plugin('lukaskleinschmidt/types', [
    'options' => [
        'aliases'    => [],
        'decorators' => [],
        'fieldsets'  => [],
        'filename'   => 'types.php',
        'force'      => false,
        'include'    => [
            'aliases',
            'blueprints',
            'decorators',
            'methods',
        ],
    ],
    'commands' => [
        'types:create' => [
            'description' => 'Create a new IDE helper file',
            'command' => function (CLI $cli) {
                Command::run($cli);
            },
            'args' => [
                'filename' => [
                    'prefix' => 'f',
                    'longPrefix' => 'filename',
                    'description' => 'The path to the helper file',
                ],
                'force' => [
                    'prefix' => 'F',
                    'longPrefix' => 'force',
                    'description' => 'Force the file creation',
                    'noValue' => true,
                ],
                'include' => [
                    'prefix' => 'i',
                    'longPrefix' => 'include',
                    'description' => 'Select the parts you want to include',
                    'noValue' => true,
                ],
            ],
        ],
    ],
    'snippets' => [
        'stubs/types-comment'  => __DIR__ . '/snippets/comment.stub.php',
        'stubs/types-template' => __DIR__ . '/snippets/template.stub.php',
    ],
]);
