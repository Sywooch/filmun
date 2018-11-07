<?php
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\search\DirectorSearch */

$dataProvider->prepare();

$this->registerJsFile('@web/js/film-view.js', ['depends' => ['app\assets\AppAsset']]);

$this->registerJs("
    $('.toggle-favourite').on('click', function(){
        var heart = $(this);
        if(heart.hasClass('loading')) {
            return false;
        }
        var person_id = $(this).closest('.film-list').data('key');
        var mode = heart.hasClass('added') ? 'remove' : 'add';
        heart.addClass('loading');
        $.get('person/toggle-favourite', {id: person_id, mode: mode}, function(json){
            if(json.success) {
                heart.toggleClass('added');
            }
            heart.removeClass('loading');
        });
    });
    
    $('.film-table-item').viewDetail();
");
?>

<?= $this->render('_search', [
    'model' => $searchModel,
]) ?>

<div class="clearfix" style="margin-top: 25px">
    <div class="pull-left">
        <b>Сотрировать: </b>
    </div>
    <div class="pull-left">
        <?= yii\widgets\LinkSorter::widget([
            'sort' => $dataProvider->sort,
        ]) ?>
    </div>
    <div class="pull-left" style="margin-left: 10px;">
        <b>| Найдено: <?= $dataProvider->getTotalCount() ?></b>
    </div>
    <div class="pull-right" style="margin-left: 10px;">
        <?php if(request()->get('hide_favourite')): ?>
            <i class="fa fa fa-heart fa-lg" style="color: #C49F47"></i>
            <?= Html::a('показать любимых', ArrayHelper::merge([$this->context->route], $_GET, ['hide_favourite' => null]), [
                'title' => 'Показать любимых',
                'style' => 'color: #C49F47',
            ]) ?>
        <?php else: ?>
            <i class="fa fa fa-heart-o fa-lg" style="color: #C49F47"></i>
            <?= Html::a('скрыть любимых', ArrayHelper::merge([$this->context->route], $_GET, ['hide_favourite' => 1]), [
                'title' => 'Скрыть любимых',
                'style' => 'color: #C49F47',
            ]) ?>

        <?php endif; ?>
    </div>
</div>

<hr>

<div id="film-list">

    <div class="row">
        <div class="col-md-7">
            <?php foreach($dataProvider->getModels() as $model): ?>
                <div class="film-list" data-key="<?= $model->id ?>">
                    <?= $this->render('_view', ['model' => $model]) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-md-5">
            <div id="detail-view" style="margin-left: 10px;"></div>
        </div>
    </div>

<?= yii\widgets\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>

</div>