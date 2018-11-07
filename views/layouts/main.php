<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

AppAsset::register($this);

if(isset($this->params['jsParams'])) {
    $jsParams = yii\helpers\Json::encode($this->params['jsParams']);
    $this->registerJs("var params = {$jsParams};", yii\web\View::POS_HEAD);
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link href="<?= Yii::getAlias('@web/favicon.png') ?>" rel="shortcut icon" type="image/png" />
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Html::img('@web/favicon.png') . ' Фильмун',
        'brandUrl' => Yii::$app->homeUrl,
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav', 'style' => 'margin-left:20px;'],
        'items' => [
            ['label' => 'Фильмы', 'url' => ['film/index', 'is_series' => '0']],
            ['label' => 'Сериалы', 'url' => ['film/index', 'is_series' => '1']],
            ['label' => 'Расписание', 'url' => ['film/schedule'], 'visible' => !user()->isGuest],
            ['label' => 'Режиссеры', 'url' => ['director/index'], 'visible' => !user()->isGuest],
            ['label' => 'История', 'url' => ['browse/index'], 'visible' => !user()->isGuest],
            ['label' => 'Персональное', 'items' => [
                ['label' => 'Оценки', 'url' => ['film/marks'], 'visible' => !user()->isGuest],
                ['label' => 'Хочу посмотреть', 'url' => ['film/wanted']],
                ['label' => 'Рекомендации', 'url' => ['film/recommend']],
                ['label' => 'От любимых режиссеров', 'url' => ['film/from-favourite-directors']],
                ['label' => 'От любимых режиссеров (не вышли)', 'url' => ['film/from-favourite-directors-not-finish']],
                ['label' => 'Режиссер является сценаристом', 'url' => ['film/director-is-screenwriter']],
            ], 'visible' => !user()->isGuest],
        ],
        'activateParents' => true,
        'encodeLabels' => false,
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            //['label' => 'Сравнить базы', 'url' => ['difference/index']],
            user()->isGuest ?
                ['label' => 'Login', 'url' => ['site/login']] :
                ['label' => '<i class="fa fa-user"></i> ' . identity()->username, 'items' => [
                    ['label' => 'Настройки', 'url' => ['user/settings']],
                    ['label' => 'Онлайн просмотр', 'url' => ['acestream/index']],
                    ['label' => 'Выход', 'url' => ['site/logout'], 'linkOptions' => ['data-method' => 'post']],
                ]],
            ['label' => '<i class="fa fa-gear"></i>', 'items' => [
                ['label' => 'Торенты', 'url' => ['torrent/index']],
                ['label' => 'Торенты - каталог', 'url' => ['torrent/catalog']],
                ['label' => 'Статистика', 'url' => ['report/index']],
                ['label' => 'Юзеры', 'url' => ['user/index']],
            ], 'visible' => user()->can('admin')],
        ],
        'encodeLabels' => false,
    ]);
    ?>
    <form class="navbar-form navbar-right" role="search" style="margin-right: 20px;" action="<?= yii\helpers\Url::to(['film/search']) ?>">
        <div class="form-group">
            <div class="input-group">
                <?php Html::textInput('term', request()->get('term'), [
                    'placeholder' => 'Поиск',
                    'class' => 'form-control',
                ])?>

                <?= yii\jui\AutoComplete::widget([
                    'name' => 'term',
                    'value' => request()->get('term'),
                    'clientOptions' => [
                        'source' => \yii\helpers\Url::to(['film/auto-complete']),
                    ],
                    'options' => [
                        'placeholder' => 'Поиск',
                        'class' => 'form-control',
                    ],
                ]) ?>

                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                </span>
            </div>
        </div>
    </form>
    <?php
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>

    <div class="film-sidebar-wrap"></div>

    <div class="film-sidebar">

    </div>
</div>

<div class="scroll-top">
    <i class="fa fa-angle-up"></i>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; Filmun <?= date('Y') ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
