<?php
namespace app\controllers;

use app\components\KinopoiskNewTrailerParser;
use app\models\Torrent;
use Yii;
use yii\db\Query;
use yii\helpers\Html;
use yii\helpers\Url;
use Zend\Http;
use app\models\Proxy;
use app\models\Film;
use app\models\FilmMark;
use app\models\FilmPerson;
use app\models\FilmWanted;
use app\components\AmazonS3;
use app\models\PersonFavourite;
use app\models\search\FilmSearch;
use yii\web\NotFoundHttpException;
use app\components\KinopoiskTrailerParser;
use app\helpers\Translit;
use app\models\SeriesEpisode;
use app\components\KinopoiskParser;
use yii\web\Controller;
use yii\filters\AccessControl;

class FilmController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionView($id)
    {
        set_time_limit(0);

        $model = $this->findModel($id);

        $this->view->title = $model->getFullName();

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionInlineView($id)
    {
        set_time_limit(0);

        $model = $this->findModel($id);
        /*$till = time() - 3600*24*3;
        if($model->last_check_at < time() - 3600*24 && (empty($model->premiere) || $model->premiere > $till)) {
            try {
                $parser = new KinopoiskParser($model->url);
                $parser->cache = false;
                $model->importFromParser($parser);
                if($model->hasErrors()) {
                    $model->refresh();
                } else {
                    $model->updateAttributes(['last_check_at' => time()]);
                }
            } catch(\Exception $e) {
                $model->refresh();
            }
        }*/

        return $this->renderAjax('_inline_view', [
            'model' => $model,
        ]);
    }

    public function actionSchedule()
    {
        $models = SeriesEpisode::find()
            ->from(['t' => SeriesEpisode::tableName()])
            ->join('JOIN', '{{%film_wanted}} fw', 'fw.film_id = t.film_id')
            ->andWhere(['user_id' => user()->id])
            ->andWhere(['>=', 'premiere', time() - 3600*24*14])
            ->orderBy(['premiere' => SORT_ASC])
            ->limit(50)
            ->all();

        return $this->render('schedule', [
            'models' => $models,
        ]);
    }

    public function actionMarked()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id');
        $query->andWhere(['fm.user_id' => user()->id]);

        $attributes = $dataProvider->sort->attributes;
        $attributes = array_merge([
            'add_at' => [
                'default' => SORT_DESC,
                'label' => 'оценен',
                'asc' => ['fm.created_at' => SORT_ASC],
                'desc' => ['fm.created_at' => SORT_DESC],
            ],
        ], $attributes);

        $dataProvider->setSort([
            'defaultOrder' => ['add_at' => SORT_DESC],
            'attributes' => $attributes,
        ]);

        $this->view->title = 'Оцененные фильмы';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionRecommend()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_recommend}} fr', 'fr.film_id = t.id');
        $query->andWhere(['fr.user_id' => user()->id]);

        $this->view->title = 'Персональные рекомендации';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionWanted()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_wanted}} fw', 'fw.film_id = t.id');
        $query->andWhere(['fw.user_id' => user()->id]);

        $attributes = $dataProvider->sort->attributes;
        $attributes = array_merge([
            'add_at' => [
                'default' => SORT_DESC,
                'label' => 'добавлению в список',
                'asc' => ['fw.created_at' => SORT_ASC],
                'desc' => ['fw.created_at' => SORT_DESC],
            ],
        ], $attributes);

        $dataProvider->setSort([
            'defaultOrder' => ['add_at' => SORT_DESC],
            'attributes' => $attributes,
        ]);

        $this->view->title = 'Хочу посмотреть';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionDirectorIsScreenwriter()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;
        $searchModel->status = Film::STATUS_CAME_OUT;

        $dataProvider = $searchModel->search();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->join('JOIN', '{{%film_person}} dir', 'dir.film_id = t.id AND dir.role =:dir_role', ['dir_role' => FilmPerson::ROLE_DIRECTOR]);
        $query->join('JOIN', '{{%film_person}} scr', 'scr.film_id = t.id AND scr.role =:scr_role', ['scr_role' => FilmPerson::ROLE_SCREENWRITER]);
        $query->andWhere('dir.person_id = scr.person_id');
        $query->andHaving('COUNT(dir.person_id) = 1 AND COUNT(scr.person_id) = 1');

        $this->view->title = 'Режиссер является сценаристом';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionFromFavouriteDirectors()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;
        $searchModel->status = Film::STATUS_CAME_OUT;

        $dataProvider = $searchModel->search();

        $with_ids = PersonFavourite::find()->andWhere(['user_id' => user()->id])->select('person_id')->column();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_person}} favp', 'favp.film_id = t.id');
        $query->andFilterWhere(['in', 'favp.person_id', $with_ids]);
        $query->andFilterWhere(['favp.role' => [FilmPerson::ROLE_DIRECTOR]]);

        $this->view->title = 'От любимых режиссеров';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionFromFavouriteDirectorsNotFinish()
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        $with_ids = PersonFavourite::find()->andWhere(['user_id' => user()->id])->select('person_id')->column();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_person}} favp', 'favp.film_id = t.id');
        $query->andFilterWhere(['in', 'favp.person_id', $with_ids]);
        $query->andFilterWhere(['favp.role' => [FilmPerson::ROLE_DIRECTOR]]);

        $statusList = [
            Film::STATUS_PRODUCTION_COMPLETED,
            Film::STATUS_POST_PRODUCTION,
            Film::STATUS_PREPARING_FOR_SHOOTING,
            Film::STATUS_SHOOTING_PROCESS
        ];
        $query->andWhere(['t.status' => $statusList]);


        $this->view->title = 'От любимых режиссеров';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionIndex($is_series = 0)
    {
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->is_series = $is_series;
        $searchModel->status = Film::STATUS_CAME_OUT;
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        $this->view->title = $is_series ? 'Сериалы' : 'Фильмы';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionMarks($user_id = null)
    {
        $user_id or $user_id = user()->id;
        $searchModel = new FilmSearch();
        $searchModel->load($_GET);
        $searchModel->status = Film::STATUS_CAME_OUT;
        $searchModel->user_id = user()->id;

        $dataProvider = $searchModel->search();

        /** @var Query $query */
        $query = $dataProvider->query;
        $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
            'user_id' => $user_id,
        ]);
        $query->andWhere('fm.user_id IS NOT NULL');
        $query->andWhere('fm.mark >= 8');


        $this->view->title =  'Оценки';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionModalTorrents($id)
    {
        Yii::$app->response->format = 'json';

        $model = $this->findModel($id);

        $json = [];
        $json['content'] = $this->renderAjax('modal-torrents', [
            'model' => $model,
        ]);
        return $json;
    }

    public function actionPutMark($id, $mark)
    {
        Yii::$app->response->format = 'json';
        $model = $this->findModel($id);
        FilmMark::create($model->id, user()->id, $mark);

        //ignore_user_abort(true);
        //$parser = new KinopoiskUser(identity());
        //$parser->putMark($model->url, $model->kp_internal_id, $mark);

        return [
            'success' => true,
        ];
    }

    public function actionToggleWanted($id, $mode)
    {
        Yii::$app->response->format = 'json';
        $model = $this->findModel($id);

        if($mode == 'add') {
            FilmWanted::create($model->id, user()->id);
        } else {
            FilmWanted::deleteAll(['film_id' => $model->id, 'user_id' => user()->id]);
        }

        //ignore_user_abort(true);
        //$parser = new KinopoiskUser(identity());
        //$parser->wanted($model->url, $model->kp_internal_id, $mode);

        return ['success' => true];
    }

    public function actionSearch($term)
    {
        $term = trim($term);

        $models = Film::find()
            ->limit(500)
            ->orWhere(['like', 'name', $term])
            ->orWhere(['like', 'original_name', $term])
            ->orderBy(['imdb_mark_votes' => SORT_DESC])
            ->all();

        usort($models, function(Film $model_1, Film $model_2) use($term){
            return (levenshtein($term, $model_1->name) < levenshtein($term, $model_2->name)) ? -1 : 1;
        });

        $models = array_slice($models, 0, 50);

        $this->view->title = 'Результаты поиска';

        return $this->render('search', [
            'models' => $models,
            'term' => $term,
        ]);
    }

    public function actionAutoComplete($term)
    {
        Yii::$app->response->format = 'json';

        $json = [];

        $search = trim($term);
        $search = mb_strtolower($search, 'utf-8');
        $search = Translit::t($search);
        $search = metaphone($search);

        $models = Film::find()
            ->andWhere(['like', 'search_text', $search])
            ->orderBy(['name' => SORT_ASC])
            ->limit(100)
            ->all();

        usort($models, function(Film $model_1, Film $model_2) use($term){
            return (levenshtein($term, $model_1->name) < levenshtein($term, $model_2->name)) ? -1 : 1;
        });

        $models = array_slice($models, 0, 10);

        foreach ($models as $model) {
            /* @var Film $model */
            $json[] = [
                'id' => $model->id,
                'label' => $model->name . ' (' . $model->year . ' г.)',
                'value' => $model->name,
            ];
        }
        return $json;
    }

    public function actionImage($id)
    {
        $model = $this->findModel($id);
        $image_url = Url::to('@web/img/film-no-img.png', true);
        if($model->image_url && empty($model->amazon_url)) {
            /** @var AmazonS3 $amazons3 */
            $amazons3 = Yii::$app->get('amazons3');
            $client = new Http\Client($model->image_url);
            $client->setHeaders(array_merge(KinopoiskParser::headers(), [
                'Referer' => 'https://www.google.com.ua/',
            ]));
            //Proxy::rand()->apply($client);
            $response = $client->send();
            $image_url = $amazons3->putImage($response->getBody(), 'film/' . md5($model->image_url) . '.jpg');
            $model->updateAttributes(['amazon_url' => $image_url]);
        }
        $this->redirect($image_url);
    }

    public function actionLoadTrailer($id)
    {
        Yii::$app->response->format = 'json';

        set_time_limit(0);
        ini_set("memory_limit", "512M");

        $files = glob(Yii::getAlias('@webroot/trailer/*.mp4'));
        usort($files, function($a, $b) {
            return filemtime($a) < filemtime($b);
        });
        $files = array_slice($files, 100);
        foreach ($files as $file) {
            unlink($file);
        }

        $model = $this->findModel($id);
        $path = Yii::getAlias('@webroot/trailer/' . $model->id . '.mp4');
        if(!file_exists($path) || true) {
            if($model->trailer_url) {
                $parser = new KinopoiskTrailerParser($model->trailer_url);
            } else {
                $parser = new KinopoiskNewTrailerParser($model->new_trailer_url);
            }
            $content = $parser->getContent();
            file_put_contents($path, $content);
        }

        return ['success' => true];
    }

    /**
     * @param $id
     * @return Film
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Film::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемая страница не существует.');
        }
    }
}