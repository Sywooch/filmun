<?php
namespace app\controllers;

use app\components\KinopoiskMarkIterator;
use app\components\KinopoiskMyListIterator;
use app\components\KinopoiskParser;
use app\components\KinopoiskRecommendIterator;
use app\models\ChangePasswordForm;
use app\models\Film;
use app\models\FilmMark;
use app\models\FilmRecommend;
use app\models\FilmWanted;
use app\models\KpSettingForm;
use app\models\User;
use app\models\UserSettingForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\filters\AccessControl;

class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'roles' => ['@'],
                        'allow' => true,
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionImportFromKp()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $user = identity();
        /** @var User $user */
        if(empty($user->kp_login) || empty($user->kp_password)) {
            echo 'Не указан логин и пароль КиноПоиск';
            exit;
        }

        echo "<h3>Загрузка оценок</h3>";
        ob_flush();
        flush();

        $parsers = new KinopoiskMarkIterator($user->kp_login, $user->kp_password);
        foreach($parsers as $parser) {
            $model = Film::importFromKp($parser->getInternalId());
            if($model) {
                //FilmMark::create($model->id, $user->id, $parser->params['myMark']);
            }

            echo '. ';
            ob_flush();
            flush();
        }

        echo "<h3>Хочу посмотреть</h3>";
        ob_flush();
        flush();
        $parsers = new KinopoiskMyListIterator($user->kp_login, $user->kp_password);
        foreach($parsers as $parser) {
            $model = Film::importFromKp($parser->getInternalId());
            if($model) {
                //FilmWanted::create($model->id, $user->id);

                echo '. ';
                ob_flush();
                flush();
            }
        }

        echo "<h3>Рекомендуемые фильмы</h3>";
        ob_flush();
        flush();
        $parsers = new KinopoiskRecommendIterator($user->kp_login, $user->kp_password);
        foreach($parsers as $parser) {
            $model = Film::importFromKp($parser->getInternalId());
            if($model) {
                //FilmRecommend::create($model->id, $user->id);

                echo '. ';
                ob_flush();
                flush();
            }
        }
        $user->updateAttributes([
            'last_check_at' => time(),
            'new_check_at' => time() + 3600 * 24 * 1 + rand(3600, 3600*12)
        ]);

        echo '<h3>Готово</h3>';
    }

    public function actionSettings()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $passwordForm = new ChangePasswordForm($user);
        if ($passwordForm->load(Yii::$app->request->post()) && $passwordForm->save()) {
            Yii::$app->session->setFlash('success', 'Пароль успешно изменен');
            return $this->refresh();
        }

        $kpSettingForm = new KpSettingForm($user);
        if ($kpSettingForm->load(Yii::$app->request->post()) && $kpSettingForm->save()) {
            Yii::$app->session->setFlash('success', 'Пароль успешно изменен');
            return $this->refresh();
        }

        $userSettingForm = new UserSettingForm($user);
        if ($userSettingForm->load(Yii::$app->request->post()) && $userSettingForm->save()) {
            Yii::$app->session->setFlash('success', 'Изменения сохранены');
            return $this->refresh();
        }

        return $this->render('settings', [
            'user' => $user,
            'passwordForm' => $passwordForm,
            'kpSettingForm' => $kpSettingForm,
            'userSettingForm' => $userSettingForm,
        ]);
    }
}