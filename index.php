<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\App;
use Kirby\CLI\CLI;
use Kirby\Toolkit\Str;

@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/helpers.php';

App::plugin('lukaskleinschmidt/types', [
    'options' => [
        'aliases'    => [],
        'decorators' => [],
        'filename'   => 'types.php',
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
                $kirby   = $cli->kirby();
                $options = $kirby->option('lukaskleinschmidt.types');

                foreach (array_keys($options) as $key) {
                    $name = Str::kebab($key);

                    if ($cli->climate()->arguments->defined($name)) {
                        $options[$key] = $cli->arg($name);
                    }
                }

                $include = $options['include'];

                if ($include === true) {
                    $input = $cli->climate()->checkboxes('Select the parts you want to include', [
                        'aliases'    => 'Aliases',
                        'blueprints' => 'Blueprints',
                        'decorators' => 'Decorators',
                        'methods'    => 'Methods',
                    ]);

                    $include = $input->prompt();
                }

                $types = Types::instance($kirby, $options);

                if (in_array('blueprints', $include)) {
                    $types->withBlueprints();
                }

                if (in_array('methods', $include)) {
                    $types->withFieldMethods();
                    $types->withTraitMethods();
                }

                if (in_array('decorators', $include)) {
                    $types->withConfigDecorators();
                }

                $types->withOptionDecorators();

                if (in_array('aliases', $include)) {
                    $types->withAliases();
                    $types->withConfigAliases();
                }

                $types->withOptionAliases();

                $types->create();
            },
            'args' => [
                'filename' => [
                    'prefix' => 'f',
                    'longPrefix' => 'filename',
                    'description' => 'The path to the helper file',
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
