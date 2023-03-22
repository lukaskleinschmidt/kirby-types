<?= '<?php' . PHP_EOL ?>

/**
 * A helper file for Kirby, to provide autocomplete information to your IDE.
 * This file was automatically generated with the Kirby Type Hints plugin.
 *
 * @see https://github.com/lukaskleinschmidt/kirby-type-hints
 */

<?php foreach ($extensions as $namespace => $classes): ?>
namespace <?= $namespace . PHP_EOL ?>
{
<?php foreach ($classes as $class => $methods): ?>
    class <?= $class . PHP_EOL ?>
    {
<?php
    /**
     * @var \LukasKleinschmidt\Types\CustomMethod $method
     */
    foreach ($methods as $method):
?>
        <?= $method->getComment(8) . PHP_EOL ?>
        public function <?= $name = $method->getName() ?>(<?= $method->getParams()->detailed() ?>)<?= $method->getReturnType() . PHP_EOL ?>
        {
            /** @var \<?= $method->target()->getName() ?> $instance */
            return $instance-><?= $method->getAlias() ?? $name ?>(<?= $method->getParams() ?>);
        }
<?php endforeach ?>
    }
<?php endforeach ?>
}
<?php endforeach ?>