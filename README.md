# Kirby Types
Additional and extended type hints for your IDE.  
Adds the `kirby types:create` command to your project.  
This command will create a file in your projects root directory that your IDE will pick.

> **Note**
> Make sure you have the [`getkirby/cli`](https://github.com/getkirby/cli) installed to use the command

## Installation

| Types | K3                 | K4                 |          
|-------|--------------------|--------------------|
| 1.1.2 | :heavy_check_mark: | :x:                |    
| 2.0.1 | :x:                | :heavy_check_mark: |    

Require this package with composer using the following command.
```
composer require --dev lukaskleinschmidt/kirby-types:^1.1
```

## Usage
Simply run `kirby types:create` to create the type hints file.

### Command Options 
You can set the `filename`, `force` and `include` option when running the command.
```bash
kirby types:create --filename my-ide-helper --force --include
```

## Options
You can use the following options in your `config.php`.  
These are the plugin's default options.
```php
return [
    'lukaskleinschmidt.types' => [
        'aliases'    => [],
        'decorators' => [],
        'filename'   => 'types.php',
        'force'      => false,
        'include'    => [
            'aliases',
            'blueprints',
            'decorators',
            'methods',
        ],
    ],
];
```

### Aliases
You can add your own aliases you want to include.
```php
return [
    'lukaskleinschmidt.types' => [
        'aliases' => [
            'MyClass' => \LukasKleinschmidt\MyClass::class,
        ],
    ],
];
```

### Decorators
You can modify methods and their DocBlock to improve IDE type hints.  
The plugin has some [default decorators](https://github.com/lukaskleinschmidt/kirby-types/blob/main/config.php) already defined. 
```php
use LukasKleinschmidt\Types\Method;
use Kirby\Cms\Layout;

return [
    'lukaskleinschmidt.types' => [
        'decorators' => [
            Layout::class => [
                'columns' => [
                    '@return \Kirby\Cms\LayoutColumns|\Kirby\Cms\LayoutColumn[]',
                ],
            ],
        ],
    ],
];
```

## License
MIT

## Credits
- [Lukas Kleinschmidt](https://github.com/lukaskleinschmidt)
