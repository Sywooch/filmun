<?php
namespace app\controllers;

use app\components\KinopoiskParser;
use app\models\RegistrForm;
use Zend\Http;
use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'index'],
                'rules' => [
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionRegistr()
    {
        exit;
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new RegistrForm();
        if ($model->load(Yii::$app->request->post()) && $model->perform()) {
            Yii::$app->telegram->sendMessage([
                'chat_id' => 256984504,
                'text' => 'Регистрация - ' . $model->username,
                'parse_mode' => 'html',
            ]);

            return $this->redirect(['site/login']);
        } else {
            return $this->render('registr', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
