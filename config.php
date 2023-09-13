<?php

namespace LukasKleinschmidt\Types;

use Kirby\Cms\Blocks;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\LayoutColumns;
use Kirby\Cms\Layouts;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Structure;
use Kirby\Cms\StructureObject;
use Kirby\Cms\User;
use Kirby\Cms\Users;
use Kirby\Content\Content;
use Kirby\Content\Field;
use Kirby\Toolkit\A;

return [
    'decorators' => [
        Content::class => [
            'fields' => [
                '@return' => type(Field::class, '[]'),
            ],
        ],
        Field::class => [
            'toBlocks' => [
                '@return' => union_type(Blocks::class, [Blocks::ITEM_CLASS, '[]']),
            ],
            'toFiles' => [
                '@return' => union_type(Files::class, [File::class, '[]']),
            ],
            'toLayouts' => [
                '@return' => union_type(Layouts::class, [Layouts::ITEM_CLASS, '[]']),
            ],
            'toPages' => [
                '@return' => union_type(Pages::class, [Page::class, '[]']),
            ],
            'toUsers' => [
                '@return' => union_type(Users::class, [User::class, '[]']),
            ],
            'toStructure' => [
                '@return' => union_type(Structure::class, [StructureObject::class, '[]']),
            ],
        ],
        Layouts::ITEM_CLASS => [
            'columns' => [
                '@return' => union_type(LayoutColumns::class, [LayoutColumns::ITEM_CLASS, '[]'])
            ],
        ],
        LayoutColumns::ITEM_CLASS => [
            'blocks' => [
                '@return' => union_type(Blocks::class, [Blocks::ITEM_CLASS, '[]'])
            ],
        ],
    ],
    'fieldsets' => [
        'layout' => function (array $field) {
            if ($tabs = A::get($field, 'settings.tabs')) {
                $fields = array_reduce($tabs, function ($fields, $tab) {
                    return array_merge($fields, $tab['fields']);
                }, []);
            }

            if ($fields ??= A::get($field, 'settings.fields')) {
                return new Fieldset($fields, Layouts::ITEM_CLASS);
            }
        },
        'object' => Content::class,
        'structure' => StructureObject::class,
    ],
];
