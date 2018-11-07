<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/**
 * @var $this \yii\web\View view component instance
 * @var yii\mail\MessageInterface $message the message being composed
 * @var app\models\User $user
 * @var app\models\Film $film
 * @var app\models\Torrent[] $torrents
 */
?>

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="content-block content-block-icon">

            <strong style="margin: 0">На фильм <?= $film->getFullName() ?> найдены такие релизы</strong><br><br>

            <?= date('Y-m-d H:i:s') ?>

            <table style="width:100%;">
                <?php foreach($torrents as $torrent): ?>
                <tr>
                    <td>
                        <?php if($torrent->has_advert): ?>
                        <span style="color: #ff3701">[реклама]</span>
                        <?php endif; ?>
                        <?= Html::a($torrent->title, $torrent->url) ?><br>
                        <span style="font-size: 12px;color: #8a8a8a;"><?= $torrent->transfer_text ?></span>
                    </td>
                    <td><?= $torrent->size_text ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <tr>
        <td class="content-block" itemprop="handler" itemscope itemtype="http://schema.org/HttpActionHandler">
            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['film/view', 'id' => $film->id]) ?>" itemprop="url">Перейти к фильму</a>
        </td>
    </tr>
</table>