<?php

namespace app\controllers\api;

use Yii;
use app\models\LoginForm;
use yii\web\HttpException;

class UserController extends ApiController
{
    public function actionLogin()
    {
        if(Yii::$app->request->method !== 'POST') {
            return null;
        }

        $rawData = Yii::$app->request->getRawBody();
        $object= json_decode($rawData);

        $model = new LoginForm();
        $model->username = $object->username;
        $model->password = $object->password;
        if ($model->login()) {
            return [
                'token' => $model->getUser()->getAccessToken(),
                'user' => $model->getUser(),
            ];
        } else {
            throw new HttpException(400, 'Wrong login or password');
        }
    }

    public function actionProfile()
    {
        return user()->identity;
    }
}