<?php
use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\search\FilmSearch */

$action = isset($action) ? $action : [$this->context->route];
?>

<?php $form = ActiveForm::begin(['method' => 'get', 'action' => $action]) ?>

    <?= Html::hiddenInput('genre_id', '') ?>
    <?= Html::hiddenInput('actor_id', '') ?>
    <?= Html::hiddenInput('director_id', '') ?>
    <?= Html::hiddenInput('country_id', '') ?>
    <?= Html::hiddenInput('is_series', $model->is_series) ?>
    <?= Html::hiddenInput('sort', Yii::$app->request->get('sort')) ?>
    <?= Html::hiddenInput('hide_watched', Yii::$app->request->get('hide_watched')) ?>

    <div class="row-flex row-flex-sm" style="margin-bottom: 15px;">
        <?php if($this->context->route != 'film/index'): ?>
        <div class="col-flex col-flex-1">
            <?= Html::dropDownList('is_series', $model->is_series, ['0' => 'Фильм', '1' => 'Сериал'], ['class' => 'form-control', 'id' => 'is_series', 'prompt' => '']) ?>
        </div>
        <?php endif; ?>
        <div class="col-flex col-flex-2">
            <?= Html::dropDownList('genre_id', null, app\models\Genre::ownList(), ['id' => 'genre_id', 'style' => 'width:100%', 'prompt' => '']) ?>
        </div>
        <div class="col-flex col-flex-2">
            <?= Html::dropDownList('country_id', null, app\models\Country::ownList(), ['id' => 'country_id', 'style' => 'width:100%', 'prompt' => '']) ?>
        </div>
        <div class="col-flex col-flex-2">
            <select id="actor_id" style="width: 100%;"></select>
        </div>
        <div class="col-flex col-flex-2">
            <select id="director_id" style="width: 100%;"></select>
        </div>
        <div class="col-flex col-flex-1">
            <?= Html::dropDownList('max_quality', $model->max_quality, $model->getMaxQualityList(), ['class' => 'form-control', 'id' => 'max_quality', 'prompt' => '']) ?>
        </div>
    </div>

    <div class="row-flex" style="margin-bottom: 15px;">
        <div class="col-flex col-flex-1">
            <?= Html::activeTextInput($model, 'year') ?>
        </div>
        <div class="col-flex" style="widows: 300px;">
            <ul class="mark-tabs">
                <li><strong>Рейтинг</strong></li>
                <li><a href="#tab-imdb">IMDb</a></li>
                <li><a href="#tab-kp">КиноПоиска</a></li>
                <li><a href="#tab-min_votes">Мин. оценок</a></li>
                <li><a href="#tab-critic">Критиков</a></li>
                <li><a href="#tab-user_review">Рецензий</a></li>
            </ul>

            <div style="position: relative;overflow: hidden">
                <div style="margin-top:-17px;" class="mark-tab" id="tab-imdb">
                    <?= Html::activeTextInput($model, 'imdb_mark') ?>
                </div>
                <div style="margin-top:-17px;" class="mark-tab" id="tab-kp">
                    <?= Html::activeTextInput($model, 'kp_mark') ?>
                </div>
                <div style="margin-top:-17px;" class="mark-tab" id="tab-min_votes">
                    <?= Html::activeTextInput($model, 'min_votes') ?>
                </div>
                <div style="margin-top:-17px;" class="mark-tab" id="tab-critic">
                    <?= Html::activeTextInput($model, 'critic_rating') ?>
                </div>
                <div style="margin-top:-17px;" class="mark-tab" id="tab-user_review">
                    <?= Html::activeTextInput($model, 'user_review_rating') ?>
                </div>
            </div>
        </div>
    </div>

<div class="search-info">
    <div class="row-flex row-flex-sm">
        <div class="col-flex col-flex-1">
            <div id="active-filters">
                <?php foreach($model->getGenres() as $genre): ?>
                    <?= $this->render('_filter-tag', [
                        'label' => $genre->name,
                        'name' => "genre_id[{$genre->id}]",
                        'value' => $genre->without_it ? -$genre->id : $genre->id,
                    ]) ?>
                <?php endforeach; ?>

                <?php foreach($model->getActors() as $persons): ?>
                    <?= $this->render('_filter-tag', [
                        'label' => $persons->name,
                        'name' => "actor_id[{$persons->id}]",
                        'value' => $persons->without_it ? -$persons->id : $persons->id,
                    ]) ?>
                <?php endforeach; ?>

                <?php foreach($model->getDirectors() as $persons): ?>
                    <?= $this->render('_filter-tag', [
                        'label' => $persons->name,
                        'name' => "director_id[{$persons->id}]",
                        'value' => $persons->without_it ? -$persons->id : $persons->id,
                    ]) ?>
                <?php endforeach; ?>

                <?php foreach($model->getCountries() as $country): ?>
                    <?= $this->render('_filter-tag', [
                        'label' => $country->name,
                        'name' => "country_id[{$country->id}]",
                        'value' => $country->without_it ? -$country->id : $country->id,
                    ]) ?>
                <?php endforeach; ?>

                <?php if($model->max_quality): ?>
                    <?= $this->render('_filter-tag', [
                        'label' => $model->getBeauty('max_quality'),
                        'name' => "max_quality",
                        'value' => $model->max_quality,
                    ]) ?>
                <?php endif; ?>
            </div>
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