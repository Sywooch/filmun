<?php
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;
use app\models\Person;
use app\models\FilmPerson;
use app\models\Torrent;

/* @var $this yii\web\View */
/* @var $model app\models\Film */
/* @var $torrent app\models\Torrent */
/* @var $actor app\models\Person */

$btn_id = 'download-torrents-' . $model->id;

$this->registerJs("
    $('.trailer i').on('click', function(){
        $(this).removeClass('fa-play-circle-o').addClass('fa-spinner fa-spin');
        $.get('film/load-trailer', { id: {$model->id} }, function(json){
            if(json.success) {
                var src = url('trailer/{$model->id}.mp4');
                var video = $('<video>')
                    .css({width: '100%'})
                    .attr('controls', 'controls')
                    .attr('autoplay', 'autoplay')
                    .attr('src', src);
                $('.trailer').replaceWith(video);
            }
        })
    });
");

$this->registerJs(<<<JS
    $(function(){
        var btn = $('#{$btn_id}');
        
        $.get('torrent/better', { film_id: {$model->id} }, function(json){
            if(json.success) {
                $('.btn', btn).prop('disabled', false);
            } else {
                $('.btn-better', btn).text('Нет доступных раздач');
                $('.btn-show-all .badge', btn).text(0);
                return false;
            }
            $('.btn-better', btn).data('key', json.data.id).html(json.data.text);
            if(json.data.max_quality >= 5) {
                btn.addClass('btn-group-success');
            } else if(json.data.max_quality >= 3) {
                btn.addClass('btn-group-warning');
            } else if(json.data.max_quality) {
                btn.addClass('btn-group-danger');
            }
            $('.btn-show-all .badge', btn).text(json.data.count);
        });
        
        $('.btn-better', btn).on('click', function(){
            var torrent_id = $(this).data('key');
            var torrent_url = url('torrent/download', {film_id: {$model->id}, id: torrent_id});
            window.open(torrent_url);
        })
    })

JS
);
?>

<div class="row-flex row-flex-xs" style="width: 100%">
    <div class="col-flex col-flex-1" style="padding-left: 0">
        <h3 style="margin: 0 5px 5px 5px">

            <?php if(!user()->isGuest): ?>
                <span class="toggle-wanted <?= $model->inWanted(user()->id) ? 'added' : '' ?>" title="Хочу посмотреть" data-key="<?= $model->id ?>">
                    <i class="fa fa-bookmark-o"></i>
                    <i class="fa fa-bookmark"></i>
                    <i class="fa fa-refresh fa-pulse"></i>
                </span>
            <?php endif; ?>

            <?= $model->name ?>
        </h3>

        <div style="margin: 0 5px;color: #5d5d5d;" data-id="<?= $model->id ?>">
            <?php if($model->original_name): ?>
                <?= $model->original_name ?>,
            <?php endif; ?>
            <?= $model->year ?> г.
        </div>

    </div>
    <div class="col-flex">
        <?= Html::beginTag('div', ['class' => 'btn-group btn-group-lg btn-download-torrents', 'id' => $btn_id])?>
            <?php
            echo Html::button('Загрузка...', ['class' => 'btn btn-better', 'disabled' => 'disabled']);
            echo Html::button('<span class="badge"></span>', [
                'class' => 'btn btn-show-all',
                'onclick' => "$.modal('film/modal-torrents', { id: {$model->id} })",
                'disabled' => 'disabled',
            ]);
            ?>
        <?= Html::endTag('div') ?>
    </div>
</div>

<hr>

<div class="row-flex row-flex-xs" style="width: 100%">
    <div class="col-flex col-flex-1">
        <strong>КиноПоиск</strong>
        <div class="mark">
            <?= $model->getKpMarkDecorate() ?>
        </div>
    </div>
    <div class="col-flex col-flex-1">
        <strong>IMDb</strong>
        <div class="mark">
            <?= $model->getIMDbMarkDecorate() ?>
        </div>
    </div>
    <div class="col-flex col-flex-1">
        <strong>Рейтинг критиков</strong>
        <div class="mark">
            <?= $model->getCriticVotesDecorate() ?>
        </div>
    </div>
    <div class="col-flex col-flex-1">
        <strong>Рейтинг рецензий</strong>
        <div class="mark">
            <?= $model->getUserReviewDecorate() ?>
        </div>
    </div>
    <div class="col-flex col-flex-1">
        <strong>Metacritic</strong>
        <div class="mark">
            <?= $model->getMetacriticDecorate() ?>
        </div>
    </div>
</div>

<?php
$actors = Person::find()->from(['t' => Person::tableName()])
    ->join('JOIN', '{{%film_person}} fp', 'fp.person_id = t.id')
    ->andWhere(['fp.film_id' => $model->id])
    ->andWhere(['fp.role' => FilmPerson::ROLE_ACTOR])
    ->orderBy(['fp.position' => SORT_ASC])
    ->all();
?>

<div class="clearfix" style="margin: 5px 0">
    <?php foreach($actors as $actor): ?>
        <a href="<?= \yii\helpers\Url::to(['film/index', 'actor_id' => [$actor->id], 'sort' => '-premiere'])?>">
            <div class="actor-item">
                <?= Html::img($actor->getImageUrl()) ?>
                <div class="actor-name"><?= $actor->name ?></div>
            </div>
        </a>

    <?php endforeach; ?>
</div>

<?= \yii\widgets\DetailView::widget([
    'model' => $model,
    'attributes' => [
        ['attribute' => 'genre', 'value' => implode(', ', ArrayHelper::getColumn($model->genres, 'name')), 'visible' => (bool) $model->genres],
        ['attribute' => 'country', 'value' => implode(', ', ArrayHelper::getColumn($model->countries, 'name')), 'visible' => (bool) $model->countries],
        [
            'attribute' => 'director',
            'value' => implode('<br>', ArrayHelper::getColumn($model->directors, function(Person $person) use($model){
                return $person->getDirectorText($model->id);
            })),
            'visible' => (bool) $model->directors,
            'format' => 'html'
        ],
        ['attribute' => 'screenwriter', 'value' => implode(', ', ArrayHelper::getColumn($model->screenwriters, 'name')), 'visible' => (bool) $model->screenwriters],
        ['attribute' => 'slogan', 'visible' => (bool) $model->slogan],
        ['attribute' => 'budget_text', 'visible' => (bool) $model->budget_text],
        ['attribute' => 'box_office', 'visible' => (bool) $model->box_office],
        ['attribute' => 'premiere', 'format' => 'date', 'visible' => (bool) $model->premiere],
        ['attribute' => 'release_dvd', 'format' => 'date', 'visible' => (bool) $model->release_dvd],
        ['attribute' => 'release_blu_ray', 'format' => 'date', 'visible' => (bool) $model->release_blu_ray],
    ],
]) ?>

<?php if($model->trailer_url || $model->new_trailer_url): ?>
    <div class="trailer" title="Смотреть трейлер" style="background-image: url(<?= $model->getImageUrl() ?>);">
        <div class="overlay"></div>
        <i class="fa fa-play-circle-o"></i>
    </div>
<?php endif; ?>

<p style="margin: 10px 0;">
    <?= $model->description ?>
</p>

<p>
    <strong>Поиск: </strong>
    <?= Html::a('nnm-club.me', 'http://nnm-club.me/forum/tracker.php?nm=' . urlencode($model->original_name ? $model->original_name : $model->name), [
        'target' => '_blank',
    ]) ?>
    |
    <?= Html::a('rutracker.org', 'https://rutracker.org/forum/tracker.php?nm=' . urlencode($model->original_name ? $model->original_name : $model->name), [
        'target' => '_blank',
    ]) ?>
    |
    <?= Html::a('0day.kiev.ua', 'https://tracker.0day.kiev.ua/browse.php?search=' . urlencode(mb_convert_encoding($model->original_name ? $model->original_name : $model->name, 'cp1251', 'utf-8')), [
        'target' => '_blank',
    ]) ?>
    |
    <?= Html::a('kinopoisk.ru', $model->url, [
        'target' => '_blank',
    ]) ?>
    |
    <?= Html::a('imdb.com', 'http://www.imdb.com/find?q=' . urlencode($model->original_name ? $model->original_name : $model->name), [
        'target' => '_blank',
    ]) ?>
</p>

<?php if($model->is_series): ?>

    <?php foreach($model->seasons as $season): ?>

    <h4 onclick="$(this).toggleClass('active').next().toggle();" class="series-season clearfix">
        <div class="pull-left"><?= $season->name ?></div>
        <div class="pull-right"><?= $season->year ?> г.</div>
    </h4>

    <table class="table table-condensed table-hover no-header" style="display: none;">
        <?php foreach($season->episodes as $episode): ?>
        <tr>
            <td width="1%"><?= $episode->number ?></td>
            <td><?= $episode->name ?></td>
            <td style="width: 110px;text-align: right;padding-right: 10px;"><?= Yii::$app->formatter->asDate($episode->premiere) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php endforeach; ?>
<?php endif; ?>
