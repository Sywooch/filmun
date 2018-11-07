<?php
namespace app\controllers\kinopoisk;

use app\components\KinopoiskNewsParser;
use app\models\Proxy;
use Yii;
use app\models\News;
use app\models\Person;
use yii\web\Controller;
use app\components\KinopoiskNewsIterator;
use Zend\Http\Client;

class NewsController extends Controller
{
    public function actionIndex()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');
        //header('Content-Type: text/html; charset=window-1251');

        /** @var Person $person */
        $query = Person::find()->from(['t' => Person::tableName()])
            ->join('JOIN', '{{%person_favourite}} pf', "pf.person_id = t.id")
            ->groupBy('t.id');

        foreach($query->each() as $i => $person) {
            $url = 'https://www.kinopoisk.ru/rss/news_actor-' . $person->kp_internal_id . '.rss';
            echo $person->name . ' - ' . $person->kp_internal_id . ' | ' . $i . '<br>';
            ob_flush();
            flush();

            /** @var KinopoiskNewsParser[] $parsers */
            $parsers = new KinopoiskNewsIterator($url);
            foreach($parsers as $parser) {
                $model = News::findOne(['kp_url' => $parser->url]);
                if($model == null) {
                    $model = new News;
                    $model->setAttributes($parser->getAttributes(), false);
                    $model->person_id = $person->id;
                    $model->save(false);

                    echo '<span style="color: white;background: green">+</span> ';
                } else {
                    echo '. ';
                }
                ob_flush();
                flush();
            }
            echo '<br>';
        }
    }
}