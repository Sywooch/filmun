<?php
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\search\DirectorSearch */

$action = isset($action) ? $action : [$this->context->route];
?>

<?php $form = ActiveForm::begin(['method' => 'get', 'action' => $action]) ?>
    <?= Html::hiddenInput('hide_favourite', request()->get('hide_favourite')) ?>
    <?= Html::hiddenInput('sort', request()->get('sort')) ?>

    <div class="search-info">
        <div class="row-flex row-flex-sm">
            <div class="col-flex col-flex-1">
                <?= Html::activeDropDownList($model, 'avg_mark', $model->getAvgMarkList(), ['prompt' => 'Общая оценка', 'class' => 'form-control']) ?>
           </div>
            <div class="col-flex col-flex-1">
                <?= Html::activeDropDownList($model, 'user_mark', $model->getUserMarkList(), ['prompt' => 'Моя оценка', 'class' => 'form-control']) ?>
            </div>
            <div class="col-flex">
                <div style="margin-bottom:2px;">
                    <?= Html::a('<i class="fa fa-undo"></i> Сбросить фильтр', [$this->context->route], ['class' => 'btn btn-default']) ?>

                    <?= Html::submitButton('<i class="fa fa-search"></i> Поиск', ['class' => 'btn btn-info']) ?>
                </div>
            </div>
        </div>
    </div>

<?php ActiveForm::end() ?>