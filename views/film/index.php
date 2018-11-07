<?php
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel app\models\search\FilmSearch */

$dataProvider->prepare();

$this->registerJsFile('@web/js/film-list.js');
$jsConfig = Json::encode(['max_year' => date('Y') + 1]);
$this->registerJs("FilmList.init($jsConfig);");
?>

<?= $this->render('_search', [
    'model' => $searchModel,
]) ?>

<div class="clearfix" style="margin-top: 25px">
    <div class="pull-left">
        <b>Сотрировать по: </b>
    </div>
    <div class="pull-left">
        <?= yii\widgets\LinkSorter::widget([
            'sort' => $dataProvider->sort,
        ]) ?>
    </div>
    <div class="pull-left" style="margin-left: 10px;">
        <b>|</b>
    </div>
    <div class="pull-left" style="margin-left: 10px;">
        <b>Найдено:<?= $dataProvider->getTotalCount() ?></b>
    </div>
    <?php if(!user()->isGuest): ?>
    <div class="pull-right" style="margin-left: 10px;">
        <?php if(request()->get('hide_watched')): ?>
            <i class="fa fa-eye-slash fa-lg" style="color: #C49F47"></i>
            <?= Html::a('показать просмотренные', ArrayHelper::merge([$this->context->route], $_GET, ['hide_watched' => null]), [
                'title' => 'Показать просмотренные',
                'style' => 'color: #C49F47',
            ]) ?>
        <?php else: ?>
            <i class="fa fa-eye fa-lg" style="color: #C49F47"></i>
            <?= Html::a('скрыть просмотренные', ArrayHelper::merge([$this->context->route], $_GET, ['hide_watched' => 1]), [
                'title' => 'Скрыть просмотренные',
                'style' => 'color: #C49F47',
            ]) ?>

        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<hr>

<div id="film-list" class="clearfix">

<?php foreach($dataProvider->getModels() as $model): ?>
    <div class="film-list" data-key="<?= $model->id ?>">
        <?= $this->render('_view', ['model' => $model]) ?>
    </div>
<?php endforeach; ?>

<?= yii\widgets\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>

</div>