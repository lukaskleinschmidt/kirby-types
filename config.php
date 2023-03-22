<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\Blocks;
use Kirby\Cms\Field;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\Layout;
use Kirby\Cms\LayoutColumn;
use Kirby\Cms\LayoutColumns;
use Kirby\Cms\Layouts;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\User;
use Kirby\Cms\Users;

return [
    'methods' => [
        Field::class => [
            'toBlocks' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Blocks::class . '|\\' . Blocks::ITEM_CLASS . '[]'
                );
            },
            'toFiles' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Files::class . '|\\' . File::class . '[]'
                );
            },
            'toLayouts' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Layouts::class . '|\\' . Layouts::ITEM_CLASS . '[]'
                );
            },
            'toPages' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Pages::class . '|\\' . Page::class . '[]'
                );
            },
            'toUsers' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Users::class . '|\\' . User::class . '[]'
                );
            },
        ],
        Layout::class => [
            'columns' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . LayoutColumns::class . '|\\' . LayoutColumns::ITEM_CLASS . '[]'
                );
            },
        ],
        LayoutColumn::class => [
            'blocks' => function (Method $method) {
                $method->comment()->tags->setContent('return',
                    '\\' . Blocks::class . '|\\' . Blocks::ITEM_CLASS . '[]'
                );
            },
        ],
    ],
];

