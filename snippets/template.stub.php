<?= '<?php' . PHP_EOL ?>

/**
 * A helper file for Kirby, to provide autocomplete information to your IDE.
 * This file was automatically generated with the Kirby Types plugin.
 *
 * @see https://github.com/lukaskleinschmidt/kirby-types
 */
<?php foreach ($methods as $namespace => $classes): ?>

namespace <?= $namespace . PHP_EOL ?>
{
<?php foreach ($classes as $class => $methods): ?>
    class <?= $class . PHP_EOL ?>
    {
<?php foreach ($methods as $method): /** @var \LukasKleinschmidt\Types\Method $method */ ?>
<?php if ($method->hasComment()): ?>
        <?= $method->getComment(8) . PHP_EOL ?>
<?php endif ?>
        public<?= r($method->static(), ' static ', ' ')?>function <?= $name = $method->getName() ?>(<?= $method->getParams()->detailed() ?>)<?= $method->getReturnType() . PHP_EOL ?>
        {
<?php if ($method->static()): ?>
            return <?= $method->target()->getShortName() ?>::<?= $method->getAlias() ?? $name ?>(<?= $method->getParams() ?>);
<?php else: ?>
            /** @var \<?= $method->target()->getName() ?> $instance */
            return $instance-><?= $method->getAlias() ?? $name ?>(<?= $method->getParams() ?>);
<?php endif ?>
        }
<?php endforeach ?>
    }
<?php endforeach ?>
}
<?php endforeach ?>
<?php foreach ($aliases as $namespace => $aliases): ?>

namespace <?= $namespace . PHP_EOL ?>
{
<?php foreach ($aliases as $alias): /** @var \LukasKleinschmidt\Types\Alias $alias */ ?>
    class <?= $alias->getName() ?> extends \<?= $alias->target()->getName() ?> {}
<?php endforeach ?>
}
<?php endforeach ?>
