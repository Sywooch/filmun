<?php
namespace app\controllers\kinopoisk;

use app\components\KinopoiskParser;
use app\components\KinopoiskPersonParser;
use app\models\Film;
use app\models\FilmPerson;
use app\models\Person;
use app\models\PersonFavourite;
use Yii;
use yii\helpers\Html;
use yii\web\Controller;

class PersonController extends Controller
{
    public function actionResave()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $query = Person::find()->andWhere('rating > 25')->andWhere('image_url IS NULL')->orderBy(['rating' => SORT_DESC]);
        /** @var Person $model */

        echo Html::tag('h3', 'Всего: ' . $query->count());

        foreach($query->each() as $i => $model) {
            if($i%100 == 0) {
                echo $i . ' ';
            }

            $parser = new KinopoiskPersonParser('https://www.kinopoisk.ru/name/' . $model->kp_internal_id . '/');

            $name = $parser->getName();
            if($name != $model->name && empty($name)) {
                $parser->clearCache();
                echo Html::a('#', $parser->url, [
                    'tagret' => '_blank',
                    'title' => $name . ' != ' . $model->name,
                ]) . ' ';
            }

            $model->image_url = $parser->getImageUrl();
            $model->name = $parser->getName();
            $model->original_name = $parser->getOriginalName();
            $model->save();

            $parser->clearContent();

            if($i%10 == 0) {
                echo '. ';
            }
            ob_flush();
            flush();
        }
    }

    public function actionCheckPerson()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $personParser = new KinopoiskPersonParser('https://www.kinopoisk.ru/name/237173/');
        $personParser->cache = false;

        $filmParsers = $personParser->getFilmsParsers();
        /** @var KinopoiskParser $filmParser */
        foreach($filmParsers as $filmParser) {
            $exists = Film::find()->andWhere(['url' => $filmParser->url])->exists();
            if($exists) {
                continue;
            } else {
                echo '. ';
                $film = new Film;
                $film->importFromParser($filmParser);
                if($film->hasErrors()) {
                    $errors = $film->getFirstErrors();
                    echo "\n" . $filmParser->url . ' - ' . current($errors) . "\n";
                }
                $filmParser->clearContent();
            }
        }
        $personParser->clearContent();
    }
}