<?php
namespace app\commands;

use app\components\ImdbParser;
use app\components\KinopoiskEpisodesParser;
use app\components\KinopoiskNewsIterator;
use app\components\KinopoiskNewsParser;
use app\components\Telegram;
use app\models\News;
use app\models\SeriesEpisode;
use app\models\SeriesSeason;
use Yii;
use app\models\Film;
use app\models\KpCatalog;
use app\models\Torrent;
use yii\console\Controller;
use app\models\TorrentCatalog;
use app\models\FilmRecommend;
use app\models\FilmWanted;
use app\models\FilmMark;
use app\models\Person;
use app\models\User;
use app\components\NnmClubParser;
use app\components\KinopoiskParser;
use app\components\KinopoiskMarkIterator;
use app\components\KinopoiskPersonParser;
use app\components\KinopoiskMyListIterator;
use app\components\KinopoiskRecommendIterator;
use yii\helpers\Html;

class ParsingController extends Controller
{
    public function actionCheckImdbMark()
    {
        set_time_limit(0);

        $query = Film::find()
            ->andWhere('imdb_internal_id IS NOT NULL')
            ->andWhere(['<', 'imdb_check_at', time()])
            ->orderBy(['imdb_check_at' => SORT_ASC])
            ->limit(50);

        /** @var Film $film */
        foreach($query->each() as $i => $model) {
            try {
                if($model->year > 2015) {
                    $check_at = time() + 3600*24*3;
                } else if($model->year > 2010) {
                    $check_at = time() + 3600*24*14;
                } else {
                    $check_at = time() + 3600*24*31*3;
                }
                $model->updateAttributes([
                    'imdb_check_at' => $check_at
                ]);

                $parser = new ImdbParser('http://www.imdb.com/title/' . $model->imdb_internal_id . '/');
                $model->updateAttributes([
                    'imdb_mark' => $parser->getMark(),
                    'imdb_mark_votes' => $parser->getCountVotes(),
                    'metacritic_score' => $parser->getMetacriticScore(),
                ]);

                $parser->clearContent();
            } catch(\Exception $e) {
                echo '# ';
            }
        }
    }

    public function actionFindImdbId()
    {
        set_time_limit(0);
        $query = Film::find()->andWhere('imdb_internal_id IS NULL');
        /** @var Film $film */
        foreach($query->each() as $film) {
            $imdb_ids = Torrent::find()->select('imdb_internal_ids')->andWhere(['kp_internal_ids' => $film->kp_internal_id . ''])->column();
            $imdb_ids = array_diff($imdb_ids, ['', null, false, 0]);
            $imdb_ids = array_unique($imdb_ids);
            if(count($imdb_ids) == 1) {
                $film->updateAttributes(['imdb_internal_id' => $imdb_ids[0]]);
            }
        }
    }

    public function actionNotifyNewTorrents()
    {
        set_time_limit(0);

        $query = Film::find()
            ->from(['t' => Film::tableName()])
            ->join('JOIN', '{{%film_wanted}} fw', 'fw.film_id = t.id')
            ->andWhere('t.is_series = 0')
            ->groupBy('t.id');

        $fromTime = mktime(0,0,0) - 3600*24;

        /** @var Film $film */
        foreach($query->each() as $i => $film) {
            //echo "[";

            $torrents = $film->getTorrents()->andWhere(['>=', 'created_at', $fromTime])->limit(50)->all();
            $users = $this->getNotifyUsers($film->id);
            foreach($users as $user) {
                $userTorrents = array_filter($torrents, function(Torrent $torrent) use($user) {
                    return $torrent->quality >= $user->notify_torrent_quality && $torrent->transfer >= $user->notify_torrent_transfer;
                });
                if($userTorrents) {
                    //echo "\n" . $user->name . ' - ' . $film->getFullName() . ' - ' . count($userTorrents) . "\n";
                    $this->sendFilmEmail($user, $film, $userTorrents);
                    $this->sendFilmTelegram($user, $film, $userTorrents);
                }
            }
            //echo $i . '] ';
        }

        echo "finish";
    }

    public function actionCheckCinema()
    {
        exit;
    }

    /**
     * @param $film_id
     * @return User[]
     */
    protected function getNotifyUsers($film_id)
    {
        return User::find()
            ->from(['t' => User::tableName()])
            ->join('JOIN', '{{%film_wanted}} fw', 'fw.user_id = t.id')
            ->andWhere(['fw.film_id' => $film_id])
            ->leftJoin('{{%film_mark}} fm', 'fm.user_id = t.id AND fm.film_id = :film_id', ['film_id' => $film_id])
            ->andWhere('fm.user_id IS NULL')
            ->andWhere('t.notify_torrent_quality IS NOT NULL')
            ->andWhere('t.notify_torrent_transfer IS NOT NULL')
            ->andWhere(['t.id' => 1])
            ->all();
    }

    protected function sendFilmTelegram(User $user, Film $film, $torrents)
    {
        if(empty($user->telegram_id)) {
            return false;
        }
        $rows = ['На фильм ' . $film->getFullName() . ' найдены такие релизы'];
        $rows[] = '';
        foreach($torrents as $torrent) {
            if($torrent->has_advert) {
                $rows[] = '[реклама] ' . Html::a($torrent->title, $torrent->url) . ' ' . $torrent->size_text;
            } else {
                $rows[] = Html::a($torrent->title, $torrent->url) . ' ' . $torrent->size_text;
            }
            if($torrent->transfer_text) {
                $rows[] = 'Перевод: ' . $torrent->transfer_text;
            }
            $rows[] = '';
        }
        $text = implode("\n", $rows);

        $this->getTelegram()->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => $text,
            'parse_mode' => 'html',
        ]);
    }

    /**
     * @return Telegram
     */
    protected function getTelegram()
    {
        return new Telegram([
            'botToken' => '348426064:AAH4pWS7hH8TpFPRBSliIKjtlC51p0X8PXs',
            'botUsername' => 'filmun_bot',
        ]);
    }

    protected function sendFilmEmail(User $user, Film $film, $torrents)
    {
        if(empty($user->email)) {
            return false;
        }

        Yii::$app->mailer->compose('newTorrent', ['user' => $user, 'film' => $film, 'torrents' => $torrents])
            ->setFrom([Yii::$app->params['noReplyEmail'] => Yii::$app->name . ' robot'])
            ->setTo($user->email)
            ->setSubject($film->getFullName())
            ->send();
    }

    public function actionUpdateTorrent()
    {
        $query = Torrent::find()
            ->andWhere(['like', 'title', 'обновляемая'])
            ->andWhere(['<', 'new_check_at', time()])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->limit(30)
        ;

        /** @var Torrent $torrent */
        foreach($query->each() as $torrent) {
            $parser = $torrent->getParser();
            $parser->cache = false;

            $torrent->last_check_at = time();
            $torrent->new_check_at = time() + 3600*24 + rand(3600, 3600*12);
            $torrent->importFromParser($parser);

            //echo '. ';
        }
    }

    public function actionCheckNews()
    {
        exit;
        set_time_limit(0);
        $query = Person::find()->from(['t' => Person::tableName()])
            ->join('JOIN', '{{%person_favourite}} pf', "pf.person_id = t.id")
            ->groupBy('t.id');
        /** @var Person $person */
        foreach($query->each() as $i => $person) {
            $url = 'https://www.kinopoisk.ru/rss/news_actor-' . $person->kp_internal_id . '.rss';
            /** @var KinopoiskNewsParser[] $parsers */
            $parsers = new KinopoiskNewsIterator($url);
            foreach($parsers as $parser) {
                $model = News::findOne(['kp_url' => $parser->url]);
                if($model == null) {
                    $model = new News;
                    $model->setAttributes($parser->getAttributes(), false);
                    $model->person_id = $person->id;
                    $model->save(false);
                }
            }
        }
    }

    public function actionCheckTorrent()
    {
        set_time_limit(0);

        $hour = date('H');
        if($hour < 7) {
            return;
        }

        $catalogs = TorrentCatalog::find()
            ->andWhere(['<', 'new_check_at', time()])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->limit(50)
            ->all();
        /** @var TorrentCatalog $catalog */
        foreach($catalogs as $catalog) {
            $interval = $catalog->check_interval;
            $catalog->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + $interval * 3600 + rand(-$interval * 1200, $interval * 1200)
            ]);

            $existsCount = 0;
            $parsers = $catalog->getParsers();

            //echo $catalog->id . ' - ' . $catalog->name . "\n";
            /** @var NnmClubParser $parser */
            foreach($parsers as $parser) {
                $parser->cache = false;
                if($existsCount > 400) {
                    break;
                }
                $exists = Torrent::find()->andWhere(['url' => $parser->url])->exists();
                if($exists) {
                    //echo '* ';
                    $existsCount++;
                } else {
                    $model = new Torrent;
                    $model->last_check_at = time();
                    $model->catalog_id = $catalog->id;
                    $model->tracker = $catalog->tracker;
                    $model->is_series = $catalog->is_series;
                    $model->importFromParser($parser);
                    if($model->hasErrors()) {
                        //echo '# ';
                    } else {
                        foreach($model->kp_internal_ids as $kp_internal_id) {
                            $film = Film::importFromKp($kp_internal_id);
                            if($film) {
                                $film->updateAttributes([
                                    'last_torrent_at' => $model->created_at,
                                    'max_quality' => $film->getTorrentQuality()
                                ]);
                            }
                        }
                        //echo '. ';
                        $existsCount = 0;
                    }
                }
                $parser->clearContent();
            }
           // echo "\n";
        }
    }

    public function actionCheckFilm()
    {
        set_time_limit(0);

        $catalogs = KpCatalog::find()
            ->andWhere(['<', 'new_check_at', time()])
            ->orderBy(['new_check_at' => SORT_ASC])->all();
        /** @var KpCatalog $catalog */
        foreach($catalogs as $catalog) {
            $catalog->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + $catalog->check_interval * 3600 * 24 + rand(3600, 3600*12)
            ]);

            $parsers = $catalog->getParsers();

            echo $catalog->id . ' - ' . $catalog->name . "\n";
            /** @var KinopoiskParser $parser */
            foreach($parsers as $parser) {
                $this->getFilm($parser);

                echo '. ';
            }
            echo "\n";
        }
    }

    public function actionCheckPersonFilms()
    {
        set_time_limit(0);
        header('Content-Type: text/html; charset=utf-8');

        $persons = Person::find()->from(['t' => Person::tableName()])
            ->join('JOIN', '{{%person_favourite}} pf', "pf.person_id = t.id")
            ->groupBy('t.id')
            ->limit( 10)
            ->andWhere(['<', 'new_check_at', time()])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->all();

        /** @var Person $person */
        foreach($persons as $i => $person) {
            //echo $person->name . "\n";

            $personParser = new KinopoiskPersonParser($person->getUrl());
            $personParser->cache = false;

            $filmParsers = $personParser->getFilmsParsers();
            /** @var KinopoiskParser $filmParser */
            foreach($filmParsers as $filmParser) {
                $this->getFilm($filmParser);
            }
            $personParser->clearContent();

            $person->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + 3600 * 24 * 6 + rand(3600*6, 3600*24*2)
            ]);
            //echo "\n";
        }
    }

    public function actionUpdateFilm()
    {
        $time = time() - 3600*24*31*6;
        $query = Film::find()
            ->andWhere(['is_series' => 0])
            ->andWhere(['or', ['!=', 'status', Film::STATUS_CAME_OUT], ['>', 'premiere', $time]])
            ->andWhere(['<', 'new_check_at', time()])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->limit(50);
        /** @var Film $film */
        foreach($query->each() as $film) {
            $parser = new KinopoiskParser($film->url);
            $parser->cache = false;
            $film->importFromParser($parser);

            $interval = 7;
            if($film->premiere) {
                if($film->premiere < time() + 3600 * 24 * 3) {
                    $interval = 1;
                } elseif($film->premiere < time() + 3600 * 24 * 7) {
                    $interval = 2;
                } elseif($film->premiere < time() + 3600 * 24 * 14) {
                    $interval = 4;
                }
            }

            $film->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + 3600 * 24 * $interval + rand(3600, 3600*12)
            ]);

            //echo $film->id . ' # ' . $film->name . "\n";
        }
    }

    public function actionUpdateSeries()
    {
        set_time_limit(0);
        $query = Film::find()
            ->andWhere(['is_series' => 1])
            ->andWhere([
                'or',
                ['>', 'last_episode_at', strtotime("-3 years")],
                ['>', 'year', date('Y') -3],
            ])
            ->orderBy(['new_check_at' => SORT_ASC])
            ->limit(20);
        /** @var Film $film */
        foreach($query->each() as $i => $film) {
            $this->updateEpisodes($film);

            $parser = new KinopoiskParser($film->url);
            $parser->cache = false;
            $film->importFromParser($parser);

            $film->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + 3600*24*31
            ]);

            //echo $film->id . ' ';
        }
    }

    protected function updateEpisodes(Film $filmModel)
    {
        $parser = new KinopoiskEpisodesParser($filmModel->url);
        $parser->cache = false;
        $seasons = $parser->getSeasons();
        foreach($seasons as $season) {
            $seasonModel = SeriesSeason::find()->andWhere(['film_id' => $filmModel->id, 'number' => $season['number']])->one();
            if($seasonModel == null) {
                $seasonModel = new SeriesSeason;
                $seasonModel->film_id = $filmModel->id;
                $seasonModel->number = $season['number'];
            }
            $seasonModel->name = $season['name'];
            $seasonModel->count_episodes = $season['count_episodes'];
            $seasonModel->year = $season['year'];
            $seasonModel->save(false);

            foreach($season['episodes'] as $episode) {
                $episodeModel = SeriesEpisode::find()->andWhere(['film_id' => $filmModel->id, 'season_id' => $seasonModel->id, 'number' => $episode['number']])->one();
                if($episodeModel == null) {
                    $episodeModel = new SeriesEpisode;
                    $episodeModel->film_id = $filmModel->id;
                    $episodeModel->season_id = $seasonModel->id;
                    $episodeModel->number = $episode['number'];
                }
                $episodeModel->name = $episode['name'];
                $episodeModel->original_name = $episode['original_name'];
                $episodeModel->premiere = $episode['premiere'];
                $episodeModel->save(false);
            }
        }
    }

    public function actionImportUserData()
    {
        exit;
        set_time_limit(0);

        $users = User::find()->andWhere(['<', 'new_check_at', time()])->all();
        /** @var User $user */
        foreach($users as $user) {
            if(empty($user->kp_login) || empty($user->kp_password)) {
                continue;
            }

            //echo "Загрузка оценок\n";
            $parsers = new KinopoiskMarkIterator($user->kp_login, $user->kp_password);
            foreach($parsers as $parser) {
                if($model = $this->getFilm($parser)) {
                    FilmMark::create($model->id, $user->id, $parser->params['myMark']);
                }
            }

            //echo "Хочу посмотреть\n";
            $parsers = new KinopoiskMyListIterator($user->kp_login, $user->kp_password);
            foreach($parsers as $parser) {
                if($model = $this->getFilm($parser)) {
                    FilmWanted::create($model->id, $user->id);
                }
            }

            //echo "Рекомендуемые фильмы\n";
            $parsers = new KinopoiskRecommendIterator($user->kp_login, $user->kp_password);
            foreach($parsers as $parser) {
                if($model = $this->getFilm($parser)) {
                    FilmRecommend::create($model->id, $user->id);
                }
            }
            $user->updateAttributes([
                'last_check_at' => time(),
                'new_check_at' => time() + 3600 * 24 * 1 + rand(3600, 3600*12)
            ]);
            //echo "\n";
        }
    }

    /**
     * @param KinopoiskParser $parser
     * @return Film|null
     */
    protected function getFilm(KinopoiskParser $parser)
    {
        $parser->cache = false;

        $model = Film::find()->andWhere(['kp_internal_id' => $parser->getInternalId()])->one();
        if($model === null) {
            $model = new Film;
            $model->importFromParser($parser);
        }
        $parser->clearContent();
        if($model->hasErrors()) {
            $errors = $model->getFirstErrors();
            //echo "\n" . $parser->url . ' - ' . current($errors) . "\n";
            return null;
        } else {
            //echo '. ';
        }
        return $model;
    }
}