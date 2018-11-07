<?php
namespace app\controllers;

use app\models\FilmPerson;
use Yii;
use Zend\Http;
use app\models\Proxy;
use yii\helpers\Url;
use yii\web\Controller;
use app\models\Person;
use app\components\AmazonS3;
use app\models\PersonFavourite;
use Imagine\Image\Box;
use yii\imagine\Image;
use app\components\KinopoiskPersonParser;

class PersonController extends Controller
{
    public function actionImage($id, $square = 0)
    {
        $model = Person::findOne($id);
        if(empty($model->image_url) && $model->last_check_at < time() - 3600*24*30) {
            if(empty($model->image_url)) {
                $parser = new KinopoiskPersonParser($model->url);
                $model->image_url = $parser->getImageUrl();
            }
            $model->last_check_at = time();
            $model->save(false);
        }

        if(empty($model->amazon_url) && !empty($model->image_url)) {
            /** @var AmazonS3 $amazons3 */
            $amazons3 = Yii::$app->get('amazons3');
            $client = new Http\Client($model->image_url);
            $client->setHeaders(array_merge(KinopoiskPersonParser::headers(), [
                'Referer' => 'https://www.google.com.ua/',
            ]));
            //Proxy::rand()->apply($client);
            $response = $client->send();
            $model->amazon_url = $amazons3->putImage($response->getBody(), 'person/' . md5($model->image_url) . '.jpg');
            $model->save(false);
        }
        $image_url = $model->amazon_url;
        if(empty($image_url)) {
            $image_url = Url::to('@web/img/person-no-img.png', true);
        }
        if($square) {
            $image = Image::getImagine()
                ->open($image_url)
                ->thumbnail(new Box(40, 40), 'outbound');

            $content = $image->get('jpg');
        } else {
            $content = file_get_contents($image_url);
        }
        Yii::$app->response->sendContentAsFile($content, basename($image_url), [
            'inline' => true,
            'mimeType' => 'image/jpeg'
        ]);
        //$this->redirect($image_url);
    }

    /**
     * @param $term
     * @param int $page
     * @return array
     */
    public function actionAutoComplete($term = null, $page = 1)
    {
        Yii::$app->response->format = 'json';
        $page = max(1, $page);
        $term = trim($term);

        $query = Person::find()
            ->from(['t' => Person::tableName()])
            ->andWhere(['like', 't.name', $term])
            ->groupBy('t.id');

        $json = [
            'items' => [],
            'total_count' => $query->count()
        ];
        $query->limit(10)->offset(($page-1) * 10);
        foreach ($query->all() as $model) {
            /* @var Person $model */
            array_push($json['items'], [
                'id' => $model->id,
                'text' => $model->name,
                'content' => $this->renderAjax('_auto-complete-content', ['model' => $model]),
            ]);
        }
        return $json;
    }

    public function actionToggleFavourite($id, $mode)
    {
        Yii::$app->response->format = 'json';

        $model = Person::findOne($id);

        if($mode == 'add') {
            PersonFavourite::create($model->id, user()->id, FilmPerson::ROLE_DIRECTOR);
        } else {
            PersonFavourite::remove($model->id, user()->id, FilmPerson::ROLE_DIRECTOR);
        }
        $json = [
            'success' => true,
        ];
        return $json;

        /*
        ignore_user_abort(true);

        $user = identity();
        $client = new Http\Client();
        KinopoiskParser::login($client, $user->kp_login, $user->kp_password);
        $client->setUri($model->url);
        $response = $client->send();

        preg_match("#xsrftoken = '(.+?)';#", $response->getBody(), $matches);
        $token = $matches[1];

        foreach($response->getCookie() as $cookie) {
            $client->addCookie($cookie->getName(), $cookie->getValue());
        }

        $client->resetParameters();

        $client->setUri('https://www.kinopoisk.ru/handler_stars_ajax.php');
        $client->setParameterGet([
            'token' => $token,
            'mode' => 'create_fav_folder',
            'rnd' => microtime(),
        ]);
        $client->send();

        $client->resetParameters();

        $params = [
            'token' => $token,
            'id_actor' => $model->kp_internal_id,
            'rnd' => microtime(),
        ];
        if($mode == 'add') {
            $params['to_folder'] = 745;
            $params['mode'] = 'add_actor';
        } else {
            $params['from_folder'] = 745;
            $params['mode'] = 'del_actor';
        }

        $client->setUri('https://www.kinopoisk.ru/handler_stars_ajax.php');
        $client->setParameterGet($params);
        $response = $client->send();

        if($response->getStatusCode() == 200) {
            if($mode == 'add') {
                PersonFavourite::create($model->id, user()->id);
            } else {
                PersonFavourite::deleteAll(['person_id' => $model->id, 'user_id' => user()->id]);
            }
            $json = [
                'success' => true,
            ];
        } else {
            $json = [];
            $json['notice'] = [
                'message' => 'Произошла ошибка',
                'type' => 'danger',
            ];
        }
        return $json;*/
    }
}