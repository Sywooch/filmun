<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $label string */
/* @var $value string */

$options = ['class' => 'filter-tag'];
if(is_numeric($value) && $value < 0) {
    Html::addCssClass($options, 'filter-tag-without');
}
$options['data-name'] = $name;
$options['title'] = $label;
?>
<?= Html::beginTag('div', $options)?>
    <span><?= $label ?></span>
    <?= Html::hiddenInput($name, $value) ?>
<?= Html::endTag('div') ?>
