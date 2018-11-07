<?php
use app\models;

/* @var $this yii\web\View */
/* @var $model app\models\Film */

$this->title = 'Главная';

\app\assets\CarouFredSelAsset::register($this);
$this->registerJs("

$('.slider-container').each(function(){
    $('.slider', this).carouFredSel({
        items               : 7,
        auto                : false,
        scroll : {
            items           : 7
        },
        prev                : $('.slider-prev', this),
        next                : $('.slider-next', this)
    });
})

");
$limit = 7*7;
?>
<div class="site-index">

    <div class="slider-container">

        <h1>Новинки <i class="fa fa-angle-left slider-prev"></i> <i class="fa fa-angle-right slider-next"></i></h1>

        <?php
        $query = models\Film::find()
            ->from(['t' => models\Film::tableName()])
            ->andWhere(['is_series' => 0])
            ->limit($limit)
            ->orderBy(['t.first_torrent_at' => SORT_DESC])
            ->andWhere([
                'or',
                //['>=', 't.kp_mark_votes', 500],
                ['>=', 't.imdb_mark_votes', 500]
            ])
            ->andWhere(['>', 'premiere', time()-3600*24*31*12])
            ->andWhere([
                'or',
                ['>=', 't.imdb_mark', 6],
                //['>=', 't.kp_mark', 6]
            ]);

        if(!user()->isGuest) {
            $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
                'user_id' => user()->id,
            ]);
            $query->andWhere('fm.user_id IS NULL');
        }
        ?>

        <div class="slider">
            <?php foreach($query->all() as $model): ?>

                <?= $this->render('_film', ['model' => $model])?>

            <?php endforeach; ?>
        </div>

    </div>

    <?php if(!user()->isGuest): ?>
        <div class="slider-container">
            <h1>Ожидаемые <i class="fa fa-angle-left slider-prev"></i> <i class="fa fa-angle-right slider-next"></i></h1>

            <?php
            $query = models\Film::find()
                ->from(['t' => models\Film::tableName()])
                ->andWhere(['is_series' => 0])
                ->andWhere(['>', 'premiere', time()-3600*24*31*12*3])
                ->limit($limit)
                ->orderBy(['t.first_torrent_at' => SORT_DESC]);

            $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
                'user_id' => user()->id,
            ]);
            $query->andWhere('fm.user_id IS NULL');

            $query->leftJoin('{{%film_wanted}} fw', 'fw.film_id = t.id');
            $query->andWhere(['fw.user_id' => user()->id]);
            ?>

            <div class="slider">
                <?php foreach($query->all() as $model): ?>

                    <?= $this->render('_film', ['model' => $model])?>

                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="slider-container">

        <h1>Сериалы <i class="fa fa-angle-left slider-prev"></i> <i class="fa fa-angle-right slider-next"></i></h1>

        <?php
        $query = models\Film::find()
            ->from(['t' => models\Film::tableName()])
            ->andWhere(['is_series' => 1])
            ->limit($limit)
            ->orderBy(['t.first_torrent_at' => SORT_DESC])
            ->andWhere([
                'or',
                ['>=', 't.kp_mark_votes', 500],
                ['>=', 't.imdb_mark_votes', 500]
            ])
            ->andWhere(['>', 'premiere', time()-3600*24*31*12])
            ->andWhere([
                'or',
                ['>=', 't.imdb_mark', 8],
                ['>=', 't.kp_mark', 8]
            ]);

        if(!user()->isGuest) {
            $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
                'user_id' => user()->id,
            ]);
            $query->andWhere('fm.user_id IS NULL');
        }
        ?>

        <div class="slider">
            <?php foreach($query->all() as $model): ?>

                <?= $this->render('_film', ['model' => $model])?>

            <?php endforeach; ?>
        </div>

    </div>

    <div class="slider-container">

        <h1>От любимых режиссеров <i class="fa fa-angle-left slider-prev"></i> <i class="fa fa-angle-right slider-next"></i></h1>

        <?php
        $query = models\Film::find()
            ->from(['t' => models\Film::tableName()])
            ->limit($limit)
            ->orderBy(['t.first_torrent_at' => SORT_DESC])
            ->andWhere(['is_series' => 0])
            ->andWhere([
                'or',
                ['>=', 't.kp_mark_votes', 500],
                ['>=', 't.imdb_mark_votes', 500]
            ])
            ->andWhere(['>', 'premiere', time()-3600*24*31*12*3])
            ->andWhere([
                'or',
                ['>=', 't.imdb_mark', 6],
                ['>=', 't.kp_mark', 6]
            ]);

        if(!user()->isGuest) {
            $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
                'user_id' => user()->id,
            ]);
            $query->andWhere('fm.user_id IS NULL');

            $with_ids = models\PersonFavourite::find()->andWhere(['user_id' => user()->id])->select('person_id')->column();

            $query->leftJoin('{{%film_person}} favp', 'favp.film_id = t.id');
            $query->andFilterWhere(['in', 'favp.person_id', $with_ids]);
            $query->andFilterWhere(['favp.role' => [models\FilmPerson::ROLE_DIRECTOR]]);
        }
        ?>

        <div class="slider">
            <?php foreach($query->all() as $model): ?>

                <?= $this->render('_film', ['model' => $model])?>

            <?php endforeach; ?>
        </div>

    </div>

</div>
