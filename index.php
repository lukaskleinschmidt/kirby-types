<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\App;
use Kirby\CLI\CLI;
use Kirby\Toolkit\Str;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

App::plugin('lukaskleinschmidt/types', [
    'options' => [
        'filename'         => 'types',
        'namespaceAliases' => false,
    ],
    'commands' => [
        'types:create' => [
            'description' => 'Create a new IDE helper file',
            'command' => function (CLI $cli) {
                $kirby   = $cli->kirby();
                $options = $kirby->option('lukaskleinschmidt.types');

                foreach (array_keys($options) as $key) {
                    $name = Str::kebab($key);

                    if ($cli->climate()->arguments->defined($name)) {
                        $options[$key] = $cli->arg($name);
                    }
                }

                $types = Types::instance($kirby, $options);

                $types->withBlueprintFields();
                $types->withFieldMethods();
                $types->withMethods();
                $types->withConfigMethods();
                $types->withAliases();
                $types->withConfigAliases();

                $types->create();
            },
            'args' => [
                'filename' => [
                    'prefix' => 'f',
                    'longPrefix' => 'filename',
                    'description' => 'The path to the helper file',
                ],
                'namespace-aliases' => [
                    'prefix' => 'na',
                    'longPrefix' => 'namespace-aliases',
                    'description' => 'Include namespace aliases',
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
