<?php
use yii\bootstrap\Html;
use yii\helpers\Url;
use app\models\Torrent;

/* @var $this yii\web\View */
/* @var $torrents app\models\Torrent[] */
/* @var $model app\models\Film */

$season = request()->get('season');
?>

<div class="modal-dialog" role="document" style="width: 700px;">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <h4 class="modal-title">
                Скачать с торентов
            </h4>
        </div>
        <div class="modal-body">
            <?php
            $items = [
                ['label' => 'Все сезоны', 'url' => ['film/modal-torrents', 'id' => $model->id], 'active' => empty($season)]
            ];
            foreach($model->getSeasons()->andWhere('year IS NOT NULL')->all() as $seasonModel) {
                $items[] = ['label' => $seasonModel->number, 'url' => ['film/modal-torrents', 'id' => $model->id, 'season' => $seasonModel->number]];
            }
            if(count($items) > 1) {
                echo yii\bootstrap\Nav::widget([
                    'items' => $items,
                    'options' => ['class' => 'nav-pills', 'style' => 'margin-bottom: 10px'],
                ]);
            }
            ?>

            <div style="max-height: 500px;overflow: auto">
                <?php foreach ((new Torrent)->getQualityList() as $quality => $label): ?>
                    <?php
                    $query = $model->getTorrents()->orderBy(['created_at' => SORT_DESC])
                        ->indexBy('url')
                        ->andWhere(['quality' => $quality])
                        ->andSeason($season)
                        ->limit(50);
                    $torrents = $query->all();
                    ?>
                    <?php if(count($torrents)): ?>

                    <h4 style="margin: 0 0 5px 0"><?= $label ?></h4>

                    <table class="table table-condensed table-hover no-header">
                        <tbody>
                        <?php foreach($torrents as $torrent): ?>
                            <tr class="<?= $torrent->seeders == 0 ? 'tr-no-seeders' : '' ?>">
                                <td>
                                    <a href="<?= Url::to(['torrent/download', 'id' => $torrent->id, 'film_id' => $model->id]) ?>" class="open-magic-player" data-pjax="0">
                                        <i class="fa fa-2x fa-youtube-play"></i>
                                    </a>
                                </td>
                                <td>
                                    <?php if($torrent->has_advert): ?>
                                        <span style="background: #ff3f3f;;color:white;padding: 1px 3px;font-size: 11px;">Реклама</span>
                                    <?php endif; ?>
                                    <?= Html::a($torrent->title, $torrent->url, ['target' => '_blank', 'data-pjax' => '0']) ?>
                                    <div style="font-size: 10px;">Перевод: <?= $torrent->getTransferDecorate() ?></div>
                                </td>
                                <td style="white-space: nowrap"><?= $torrent->getSizeDecorate() ?></td>
                                <td style="white-space: nowrap"><?= $torrent->getCreatedDecorate() ?></td>
                                <td>
                                    <span class="seeders"><?= $torrent->seeders + 0 ?></span><br>
                                    <div class="pull-right" style="font-size: 10px;color:dimgrey">сиды</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($torrents)): ?>
                            <tr>
                                <td colspan="3">Ничего не найдено</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>