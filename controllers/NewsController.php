<?php
namespace app\controllers;

use app\components\XmlConstructor;
use app\models\News;
use app\models\Person;
use app\models\Proxy;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use Zend\Http\Client;

class NewsController extends Controller
{
    public function actionRss($user_id)
    {
        Yii::$app->response->format = 'raw';
        Yii::$app->response->headers->set('Content-Type', 'application/xml');

        $person_ids = Person::find()->from(['t' => Person::tableName()])
            ->select('t.id')
            ->distinct()
            ->join('JOIN', '{{%person_favourite}} pf', "pf.person_id = t.id")
            ->andWhere(['pf.user_id' => $user_id])
            ->column();

        $query = News::find()->andWhere(['person_id' => $person_ids])->orderBy(['public_at' => SORT_DESC])->limit(500);
        $itemElements = [];
        /** @var News $news */
        foreach($query->each() as $news) {
            $description = preg_replace("#<a href='.+?'>Подробнее...</a>#", '', $news->description);
            $description = str_replace('<br>', '', $description);
            $description = str_replace('<br />', '', $description);
            $description = trim($description);

            if($news->kp_preview) {
                $description = Html::img($news->getImageUrl()) . $description;
            }

            $itemElements[] = ['item', 'elements' => [
                ['title', 'content' => $news->title],
                ['link', 'content' => Url::to(['news/view', 'id' => $news->id], true)],
                ['description', 'content' => $description],
                ['pubDate', 'content' => date('r', $news->public_at)],
                ['guid', 'content' => md5($news->kp_url)],
            ]];
        }

        return (new XmlConstructor)->fromArray([
            ['rss',
                'elements' => [
                    ['title', 'content' => 'Новости фильмун'],
                    ['link', 'content' => 'http://liftoff.msfc.nasa.gov/'],
                    ['description', 'content' => 'Мои новости фильмун'],
                    ['language', 'content' => 'ru-ru'],
                    ['pubDate', 'content' => date('r')],
                    ['channel', 'elements' => $itemElements]
                ],
                'attributes' => ['version' => '2.0'],
            ]
        ])->toOutput();
    }

    public function actionImage($url)
    {
        $client = new Client($url);
        Proxy::rand()->apply($client);
        $response = $client->send();
        $response->getBody();
        return Yii::$app->response->sendContentAsFile($response->getBody(), 'img.jpg');
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * @param $id
     * @return News
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = News::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не существует.');
        }
    }
}