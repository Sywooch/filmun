<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\components\Telegram;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\filters\AccessControl;

class TelegramController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionAuth()
    {
        Yii::$app->response->format = 'json';

        $user = identity();

        /** @var Telegram $telegram */
        $telegram = Yii::$app->get('telegram');
        $response = $telegram->getUpdates();

        foreach ($response->result as $update) {
            $message = $update->message;
            $text = ArrayHelper::getValue($message, 'text');
            if (StringHelper::startsWith($text, '/start')) {
                $user_id = substr($text, 7);
                if($user->id == $user_id) {
                    $user->updateAttributes([
                        'telegram_id' => $message->from->id,
                    ]);
                    return ['success' => true];
                }
            }
        }
        return [];
    }

    public function actionLogout()
    {
        //Yii::$app->response->format = 'json';

        identity()->updateAttributes([
            'telegram_id' => null,
        ]);

        return $this->redirect(['user/settings']);

        //return ['success' => true];
    }
}