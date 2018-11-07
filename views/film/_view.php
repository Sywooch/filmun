<?php
use yii\bootstrap\Html;
use app\models\FilmMark;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Film */
/* @var $person app\models\Person */

$makrId = 'user-mark-' . $model->id . '-' . rand();

$this->registerJs("
    $('#{$makrId}').barrating({
        theme: 'bootstrap-stars',
        onSelect: function(value, text, event) {
            $.get('film/put-mark', {id: $model->id, mark: value});
            $('#user-mark-value-$model->id').text(value);
        }
    });
");
$markValue = $model->getUserMark(user()->id);
?>

<div class="row-flex row-flex-sm">
    <div class="film-list-img col-flex">
        <?= Html::img($model->getImageUrl()) ?>
    </div>
    <div class="film-list-content col-flex col-flex-1">
        <div class="row-flex row-flex-sm">
            <div class="col-flex col-flex-1">
                <div class="title">
                    <?php if(!user()->isGuest): ?>
                    <span class="toggle-wanted <?= $model->inWanted(user()->id) ? 'added' : '' ?>" title="Хочу посмотреть" data-key="<?= $model->id ?>">
                        <i class="fa fa-bookmark-o"></i>
                        <i class="fa fa-bookmark"></i>
                        <i class="fa fa-refresh fa-pulse"></i>
                    </span>
                    <?php endif; ?>

                    <?= Html::encode($model->name) ?> (<?= $model->year ?> г.)
                    <?= Html::a('<i class="fa fa-share-square-o"></i>', ['film/view', 'id' => $model->id], ['target' => '_blank']) ?>
                    <?php
                    switch ($model->max_quality) {
                        case 1:
                        case 2:
                            echo '<i class="fa fa-film" style="color:red"></i>';
                            break;
                        case 3:
                        case 4:
                            echo '<i class="fa fa-film" style="color:#ffba06"></i>';
                        break;
                        case 5:
                        case 6:
                            echo '<i class="fa fa-film" style="color:#21bb01"></i>';
                            break;
                    }
                    ?>
                </div>
            </div>
            <div class="col-flex" style="white-space: nowrap;">
                <?php if($model->imdb_mark): ?>
                    <span class="mark">IMDb <?= $model->getIMDbMarkDecorate() ?></span>
                <?php else: ?>
                    <span class="mark">Нет оценки</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="row-flex row-flex-sm">
            <div class="col-flex col-flex-1">
                <p>
                    <?= implode(', ', array_slice(ArrayHelper::getColumn($model->genres, 'name'), 0, 3)) ?> |
                    <?= implode(', ', array_slice(ArrayHelper::getColumn($model->countries, 'name'), 0, 3)) ?>
                </p>
            </div>
            <?php if(!user()->isGuest): ?>
            <div class="col-flex">
                <div class="pull-left film-mark" id="user-mark-value-<?= $model->id ?>"><?= $markValue ?></div>
                <div class="pull-left">
                    <?= Html::dropDownList(null, $markValue, array_slice(range(0, 10), 1, null, true), [
                        'id' => $makrId,
                        'prompt' => '',
                    ])?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <p>
            <?= StringHelper::truncate($model->description, 400) ?>
        </p>

        <?php if($model->directors): ?>
            Режиссер: <?= $model->getDirectorsTag() ?>
        <br>
        <?php endif; ?>
        <?php if($model->actors): ?>
            В ролях: <span style="color:#8e8e8e"><?= implode(', ', array_slice(ArrayHelper::getColumn($model->actors, 'name'), 0, 5)) ?></span>
        <?php endif; ?>
    </div>
</div>