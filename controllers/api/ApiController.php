<?php
namespace app\controllers\api;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;

class ApiController extends Controller
{
    public function init()
    {
        $allowExposeHeaders = [
            'X-Pagination-Current-Page', 'X-Pagination-Page-Count',
            'X-Pagination-Per-Page', 'X-Pagination-Total-Count'
        ];
        $allowHeaders = [
            'Content-Type',
            'Authorization',
        ];
        $allowMethods = ['GET', 'POST', 'PUT', 'DELETE'];

        parent::init();
        Yii::$app->response->headers->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->headers->add('Access-Control-Expose-Headers', implode(',', $allowExposeHeaders));
        Yii::$app->response->headers->add('Access-Control-Allow-Headers', implode(',', $allowHeaders));
        Yii::$app->response->headers->add('Access-Control-Allow-Methods', implode(',', $allowMethods));
        Yii::$app->user->enableSession = false;

        $auth = new HttpBearerAuth();
        $auth->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
    }

    public function getRequestParam($key, $default = null)
    {
        $rawData = Yii::$app->request->getRawBody();
        $object = json_decode($rawData);

        return ArrayHelper::getValue($object, $key, $default);
    }
}