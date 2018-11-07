<?php
/**
 * @var yii\web\View $this
 * @var string $content
 */
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
    <h4 class="modal-title"><?= isset($header) ? $header : t('Подробнее') ?></h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <?= $content ?>
        </div>
    </div>
</div>