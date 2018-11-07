<?php
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Person */
/* @var $film app\models\Film */

$films = $model->getDirectorFilms()->orderBy(['imdb_mark' => SORT_DESC])->limit(7)->all();
?>

<div class="row-flex row-flex-sm">
    <div class="film-list-img col-flex">
        <?= Html::img($model->getImageUrl()) ?>
    </div>
    <div class="film-list-content col-flex col-flex-1">
        <div class="row-flex row-flex-sm" style="margin-bottom: 10px;">
            <div class="col-flex col-flex-1">
                <div class="title">
                    <span class="toggle-favourite <?= $model->inFavourite(user()->id) ? 'added' : '' ?>">
                        <i class="fa fa-heart-o"></i>
                        <i class="fa fa-heart"></i>
                        <i class="fa fa-refresh fa-pulse"></i>
                    </span>
                    <?= Html::encode($model->name) ?>
                    <?= Html::a('<i class="fa fa-share-square-o"></i>', ['film/index', 'director_id' => [$model->id], 'sort' => '-premiere'], ['target' => '_blank']) ?>
                </div>
            </div>
            <div class="col-flex">
                Мой рейтинг <?= number_format($model->user_mark, 1) ?>
            </div>
        </div>

        <table class="table table-hover table-condensed no-header">
            <colgroup>
                <col width="20">
                <col>
                <col width="40">
                <col width="40">
            </colgroup>
            <tbody>
            <?php foreach($model->getDirectorFilms()->orderBy(['imdb_mark' => SORT_DESC])->limit(7)->all() as $film): ?>
                <?php $mark = $film->getUserMark(user()->id); ?>
                <tr class="film-table-item <?= ($mark) ? 'success' : '' ?>" data-key="<?= $film->id ?>">
                    <td>
                        <?php if($film->is_series): ?>
                            <i class="fa fa-film" style="color: #0b93d5"></i>
                        <?php else: ?>
                            <i class="fa fa-film" style="color: #E87E04"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= $film->getFullName() ?></td>
                    <td><?= ($mark) ? $mark : '<i class="fa fa-eye-slash" title="Не смотрел" style="color: #9d9d9d"></i>' ?></td>
                    <td><?= $film->imdb_mark ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>