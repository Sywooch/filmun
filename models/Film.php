<?php
namespace app\models;

use app\components\KinopoiskParser;
use app\helpers\Translit;
use app\models\query\TorrentQuery;
use Yii;
use yii\helpers\Url;
use Zend\Http;
use yii\db\ActiveQuery;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%film}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $original_name
 * @property string $url
 * @property integer $kp_internal_id
 * @property integer $imdb_internal_id
 * @property integer $year
 * @property integer $count_seasons
 * @property integer $is_series
 * @property string $slogan
 * @property string $budget
 * @property string $budget_text
 * @property string $budget_currency
 * @property string $box_office
 * @property string $trailer_url
 * @property string $new_trailer_url
 * @property integer $premiere
 * @property integer $release_dvd
 * @property integer $release_blu_ray
 * @property double  $kp_mark
 * @property integer $kp_mark_votes
 * @property double  $imdb_mark
 * @property integer $imdb_mark_votes
 * @property integer $critic_rating
 * @property integer $critic_votes
 * @property integer $user_review_rating
 * @property integer $user_review_votes
 * @property integer $metacritic_score
 * @property integer $await_rating
 * @property integer $await_votes
 * @property integer $max_quality
 * @property integer $last_episode_at
 * @property integer $last_torrent_at
 * @property integer $last_check_at
 * @property integer $new_check_at
 * @property string $image_url
 * @property string $amazon_url
 * @property string $search_text
 * @property integer $status
 * @property string $description
 * @property array $actor_ids
 * @property array $director_ids
 *
 * @property Genre[] $genres
 * @property Director[] $directors
 * @property Person[] $actors
 * @property Person[] $operators
 * @property Person[] $screenwriters
 * @property Person[] $producers
 * @property Country[] $countries
 * @property SeriesSeason[] $seasons
 */
class Film extends ActiveRecord
{
    const STATUS_CAME_OUT = 1;
    const STATUS_PRODUCTION_COMPLETED = 2;
    const STATUS_POST_PRODUCTION = 3;
    const STATUS_PREPARING_FOR_SHOOTING = 4;
    const STATUS_SHOOTING_PROCESS = 5;
    const STATUS_ANNOUNCED = 6;
    const STATUS_UNKNOWN = 7;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%film}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'last_check_at'],
                ],
            ],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'original_name',
            'year',
            'count_seasons',
            'is_series',
            'slogan',
            'imageUrl',
            'description',
            'budget',
            'budget_currency',
            'box_office',
            'premiere',
            'release_dvd',
            'release_blu_ray',
            'kp_mark',
            'kp_mark_votes',
            'imdb_mark',
            'imdb_mark_votes',
            'critic_rating',
            'critic_votes',
            'user_review_rating',
            'user_review_votes',
            'metacritic_score',
            'await_rating',
            'await_votes',
            'status',
            'created_at',
            'max_quality',
            'director_ids',
        ];
    }

    /**
     * @return array
     */
    public function typeArrayAttributes()
    {
        return ['actor_ids', 'director_ids'];
    }

    public function extraFields()
    {
        return [
            'actors' => function(self $film) {
                $actors = $film->actors;
                $ids = $film->actor_ids;
                usort($actors, function(Person $a1, Person $a2) use ($ids) {
                    return array_search($a1->id, $ids) < array_search($a2->id, $ids) ? -1 : 1;
                });
                return array_slice($actors, 0, 10);
            },
            'directors', 'producers', 'screenwriters', 'operators', 'genres', 'countries', 'seasons',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'kp_internal_id'], 'required'],
            [['url', 'kp_internal_id'], 'unique'],
            [[
                'kp_internal_id', 'year', 'count_seasons', 'is_series', 'premiere', 'release_dvd', 'release_blu_ray',
                'kp_mark_votes', 'imdb_mark_votes', 'critic_rating', 'critic_votes', 'last_torrent_at', 'max_quality',
                'user_review_rating', 'user_review_votes', 'await_rating', 'await_votes', 'status',
            ], 'integer'],
            [['slogan', 'description'], 'string'],
            [['kp_mark', 'imdb_mark'], 'number'],
            [['name', 'original_name', 'url', 'image_url'], 'string', 'max' => 512],
            [['budget_text', 'box_office'], 'string', 'max' => 256],
            [['budget'], 'integer'],
            [['budget_currency'], 'string', 'max' => 4],
            [['trailer_url'], 'string', 'max' => 512],
            [['new_trailer_url'], 'string', 'max' => 512],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'original_name' => 'Оригинальное название',
            'url' => 'Url',
            'kp_internal_id' => 'Kp Internal ID',
            'year' => 'Год',
            'country' => 'Страна',
            'genre' => 'Жанр',
            'screenwriter' => 'Сценарист',
            'director' => 'Режиссер',
            'count_seasons' => 'Сезонов',
            'is_series' => 'Это сериал',
            'slogan' => 'Слоган',
            'budget' => 'Бюджет',
            'budget_currency' => 'Бюджет: валюта',
            'budget_text' => 'Бюджет',
            'box_office' => 'Сборы',
            'premiere' => 'Премьера',
            'release_dvd' => 'Релиз DVD',
            'release_blu_ray' => 'Релиз Blu Ray',
            'kp_mark' => 'Kp Mark',
            'kp_mark_votes' => 'Kp Mark Votes',
            'imdb_mark' => 'Imdb Mark',
            'imdb_mark_votes' => 'Imdb Mark Votes',
            'critic_rating' => 'Critic Rating',
            'critic_votes' => 'Critic Reviews',
            'image_url' => 'Image Url',
            'description' => 'Description',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getActors()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])
            ->viaTable('{{%film_person}}', ['film_id' => 'id'], function (ActiveQuery $relation) {
                $relation->andWhere(['role' => FilmPerson::ROLE_ACTOR]);
            })->orderBy(['rating' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getDirectors()
    {
        return $this->hasMany(Director::className(), ['id' => 'person_id'])
            ->viaTable('{{%film_person}}', ['film_id' => 'id'], function (ActiveQuery $relation) {
                $relation->andWhere(['role' => FilmPerson::ROLE_DIRECTOR]);
            })->orderBy(['rating' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getProducers()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])
            ->viaTable('{{%film_person}}', ['film_id' => 'id'], function (ActiveQuery $relation) {
                $relation->andWhere(['role' => FilmPerson::ROLE_PRODUCER]);
            })->orderBy(['rating' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getScreenwriters()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])
            ->viaTable('{{%film_person}}', ['film_id' => 'id'], function (ActiveQuery $relation) {
                $relation->andWhere(['role' => FilmPerson::ROLE_SCREENWRITER]);
            })->orderBy(['rating' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getOperators()
    {
        return $this->hasMany(Person::className(), ['id' => 'person_id'])
            ->viaTable('{{%film_person}}', ['film_id' => 'id'], function (ActiveQuery $relation) {
                $relation->andWhere(['role' => FilmPerson::ROLE_OPERATOR]);
            })->orderBy(['rating' => SORT_ASC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getGenres()
    {
        return $this->hasMany(Genre::className(), ['id' => 'genre_id'])
            ->viaTable('{{%film_genre}}', ['film_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCountries()
    {
        return $this->hasMany(Country::className(), ['id' => 'country_id'])
            ->viaTable('{{%film_country}}', ['film_id' => 'id']);
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return Html::a($this->getFullName(), ['film/view', 'id' => $this->id], ['target' => '_blank']);
    }

    /**
     * @param $user_id
     * @return false|null|string
     */
    public function getUserMark($user_id)
    {
        return FilmMark::find()->select('mark')->andWhere(['film_id' => $this->id, 'user_id' => $user_id])->scalar();
    }

    /**
     * @return false|null|string
     */
    public function getTorrentQuality()
    {
        $quality = $this->getTorrents()
            ->select('quality')
            ->andWhere(['>', 'transfer', 3]) // без перевода не учитываем
            ->orderBy(['quality' => SORT_DESC])
            ->scalar();

        return $quality ? $quality : null;
    }

    public function getTorrentIds()
    {
        $key = 'film-torrents-' . $this->id;
        $torrent_ids = Yii::$app->cache->get($key);
        ///$torrent_ids = false;
        if($torrent_ids === false) {
            $pYear = $this->premiere ? date('Y', $this->premiere) : null;

            $query = Torrent::find();
            $query->select('id');
            $query->limit(500);
            $query->orWhere('FIND_IN_SET(:kp_internal_id, kp_internal_ids)', ['kp_internal_id' => $this->kp_internal_id]);
            if (mb_strlen($this->name, 'utf-8') > 4) {
                if ($pYear) {
                    $query->orFilterWhere(['and',
                        'kp_internal_ids IS NULL',
                        ['like', 'title', $this->name],
                        ['or',
                            ['like', 'title', $this->year],
                            ['like', 'title', $pYear]
                        ]
                    ]);
                    if (mb_strlen($this->original_name, 'utf-8') > 4) {
                        $query->orFilterWhere(['and',
                            'kp_internal_ids IS NULL',
                            ['like', 'title', $this->original_name],
                            ['or',
                                ['like', 'title', $this->year],
                                ['like', 'title', $pYear]
                            ]
                        ]);
                    }
                } else {
                    $query->orFilterWhere(['and', 'kp_internal_ids IS NULL', ['like', 'title', $this->name]]);
                    if (mb_strlen($this->original_name, 'utf-8') > 4) {
                        $query->orFilterWhere(['and', 'kp_internal_ids IS NULL', ['like', 'title', $this->original_name]]);
                    }
                }
            }

            $torrent_ids = $query->column();
            Yii::$app->cache->set($key, $torrent_ids, 60*60);
        }
        return $torrent_ids;
    }

    /**
     * @return TorrentQuery
     */
    public function getTorrents()
    {
        $query = Torrent::find();
        $query->andWhere(['id' => $this->getTorrentIds()]);
        return $query;
    }

    /**
     * @return ActiveQuery
     */
    public function getSeasons()
    {
        return $this->hasMany(SeriesSeason::className(), ['film_id' => 'id']);
    }

    public function importFromParser(KinopoiskParser $parser)
    {
        $attributes = $parser->getAttributes();
        if($attributes['imdb_mark_votes'] < $this->imdb_mark_votes) {
            unset($attributes['imdb_mark_votes']);
            unset($attributes['imdb_mark']);
        }
        $this->setAttributes($attributes);
        if (!$this->save()) {
            return false;
        }
        foreach ($parser->getCounties() as $kp_internal_id => $name) {
            $country = Country::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmCountry::create($this->id, $country->id);
        }
        foreach ($parser->getGenres() as $kp_internal_id => $name) {
            $genre = Genre::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmGenre::create($this->id, $genre->id);
        }
        $position = 0;
        foreach ($parser->getDirectors() as $kp_internal_id => $name) {
            $person = Person::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmPerson::create($this->id, $person->id, FilmPerson::ROLE_DIRECTOR, $position++);
        }
        $position = 0;
        foreach ($parser->getScreenwriters() as $kp_internal_id => $name) {
            $person = Person::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmPerson::create($this->id, $person->id, FilmPerson::ROLE_SCREENWRITER, $position++);
        }
        $position = 0;
        foreach ($parser->getOperators() as $kp_internal_id => $name) {
            $person = Person::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmPerson::create($this->id, $person->id, FilmPerson::ROLE_OPERATOR, $position++);
        }
        $position = 0;
        foreach ($parser->getProducers() as $kp_internal_id => $name) {
            $person = Person::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmPerson::create($this->id, $person->id, FilmPerson::ROLE_PRODUCER, $position++);
        }
        $position = 0;
        foreach ($parser->getActors() as $kp_internal_id => $name) {
            $person = Person::findOrCreate(['name' => $name, 'kp_internal_id' => $kp_internal_id]);
            FilmPerson::create($this->id, $person->id, FilmPerson::ROLE_ACTOR, $position++);
        }
        return true;
    }

    public function generateSearchText()
    {
        $text = $this->name;
        if ($this->original_name) {
            $text .= ' ' . $this->original_name;
        }
        $text = mb_strtolower($text, 'utf-8');
        $text = Translit::t($text);
        return metaphone($text);
    }

    public function checkSeeders()
    {
        $torrent_ids = $this->getTorrents()->select('id')->column();
        if($torrent_ids) {
            Torrent::updateAll(['seeders' => null], ['id' => $torrent_ids]);
        }

        Torrent::checkSeeders($this->name);
        if ($this->original_name) {
            Torrent::checkSeeders($this->original_name);
        }
    }

    public function getKpMarkDecorate()
    {
        if (empty($this->kp_mark_votes)) {
            return ' - ';
        }
        $text = round($this->kp_mark, 1) . ' (' . Yii::$app->formatter->asInteger($this->kp_mark_votes) . ')';
        $options = [];
        if ($this->kp_mark < 5) {
            Html::addCssStyle($options, 'color:#D91E18;font-weight: 600;');
        } else if ($this->kp_mark < 7) {
            Html::addCssStyle($options, 'color:#E87E04');
        } else if ($this->kp_mark > 8) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        }
        return Html::tag('span', $text, $options);
    }

    public function getCriticVotesDecorate()
    {
        if (empty($this->critic_votes)) {
            return ' - ';
        }
        $text = round($this->critic_rating) . '% (' . Yii::$app->formatter->asInteger($this->critic_votes) . ')';
        $options = [];
        if ($this->critic_rating < 50) {
            Html::addCssStyle($options, 'color:#D91E18;font-weight: 600;');
        } else if ($this->critic_rating < 70) {
            Html::addCssStyle($options, 'color:#E87E04');
        } else if ($this->critic_rating > 80) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        }
        return Html::tag('span', $text, $options);
    }

    public function getUserReviewDecorate()
    {
        if (empty($this->user_review_votes)) {
            return ' - ';
        }
        $text = round($this->user_review_rating) . '% (' . Yii::$app->formatter->asInteger($this->user_review_votes) . ')';
        $options = [];
        if ($this->user_review_rating < 50) {
            Html::addCssStyle($options, 'color:#D91E18;font-weight: 600;');
        } else if ($this->user_review_rating < 70) {
            Html::addCssStyle($options, 'color:#E87E04');
        } else if ($this->user_review_rating > 80) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        }
        return Html::tag('span', $text, $options);
    }

    public function getMetacriticDecorate()
    {
        if (empty($this->metacritic_score)) {
            return ' - ';
        }
        $text = $this->metacritic_score . '%';
        $options = [];
        if ($this->metacritic_score < 50) {
            Html::addCssStyle($options, 'color:#D91E18;font-weight: 600;');
        } else if ($this->metacritic_score < 70) {
            Html::addCssStyle($options, 'color:#E87E04');
        } else if ($this->metacritic_score > 80) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        }
        return Html::tag('span', $text, $options);
    }

    public function getIMDbMarkDecorate()
    {
        if (empty($this->imdb_mark_votes)) {
            return ' - ';
        }
        $text = round($this->imdb_mark, 1) . ' (' . Yii::$app->formatter->asInteger($this->imdb_mark_votes) . ')';
        $options = [];
        if ($this->imdb_mark < 5) {
            Html::addCssStyle($options, 'color:#D91E18;font-weight: 600;');
        } else if ($this->imdb_mark < 7) {
            Html::addCssStyle($options, 'color:#E87E04');
        } else if ($this->imdb_mark > 8) {
            Html::addCssStyle($options, 'color: #19b735;font-weight: 600;');
        }
        return Html::tag('span', $text, $options);
    }

    public function generateLastEpisodeAt()
    {
        if(!$this->is_series) {
            return null;
        }
        /** @var SeriesSeason $season */
        $season = $this->getSeasons()->orderBy(['number' => SORT_DESC])->one();
        if($season == null) {
            return null;
        }
        /** @var SeriesEpisode $episode */
        $episode = $season->getEpisodes()->orderBy(['premiere' => SORT_DESC])->one();
        return $episode ? $episode->premiere : null;
    }

    /**
     * @return array
     */
    public function generateDirectorIds()
    {
        return FilmPerson::find()
            ->select('person_id')
            ->andWhere([
                'role' => FilmPerson::ROLE_DIRECTOR,
                'film_id' => $this->id,
            ])
            ->orderBy(['position' => SORT_ASC])
            ->column();
    }

    /**
     * @return array
     */
    public function generateActorIds()
    {
        return FilmPerson::find()
            ->select('person_id')
            ->andWhere([
                'role' => FilmPerson::ROLE_ACTOR,
                'film_id' => $this->id,
            ])
            ->orderBy(['position' => SORT_ASC])
            ->column();
    }

    /**
     * @param $user_id
     * @return bool
     */
    public function inWanted($user_id)
    {
        return FilmWanted::find()->andWhere(['film_id' => $this->id, 'user_id' => $user_id])->exists();
    }

    public function beforeSave($insert)
    {
        $this->search_text = $this->generateSearchText();
        $this->last_episode_at = $this->generateLastEpisodeAt();
        $this->actor_ids = $this->generateActorIds();
        $this->director_ids = $this->generateDirectorIds();
        if($insert && empty($this->created_at)) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function getFullName()
    {
        $name = $this->original_name ? $this->name . ' / ' . $this->original_name : $this->name;
        if($this->year) {
            $name .= ' (' . $this->year . ' г.)';
        }
        return $name;
    }

    public function getImageUrl()
    {
        if($this->amazon_url) {
            return $this->amazon_url;
        }
        return Url::to(['film/image', 'id' => $this->id], true);
    }

    public function getDirectorsTag()
    {
        $pieces = [];
        foreach($this->directors as $director) {
            $pieces[] = $director->getNameTag();
        }
        return implode(', ', $pieces);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if($this->id) {
            FilmBrowse::deleteAll(['film_id' => $this->id]);
            FilmCountry::deleteAll(['film_id' => $this->id]);
            FilmGenre::deleteAll(['film_id' => $this->id]);
            FilmMark::deleteAll(['film_id' => $this->id]);
            FilmPerson::deleteAll(['film_id' => $this->id]);
            FilmRecommend::deleteAll(['film_id' => $this->id]);
            FilmTopList::deleteAll(['film_id' => $this->id]);
            FilmWanted::deleteAll(['film_id' => $this->id]);
        }
        return parent::beforeDelete();
    }

    /**
     * @param $kp_internal_id
     * @return Film|null
     */
    public static function importFromKp($kp_internal_id)
    {
        $model = Film::find()->andWhere(['kp_internal_id' => $kp_internal_id])->one();
        if($model) {
            return $model;
        }
        $parser = new KinopoiskParser('https://www.kinopoisk.ru/film/' . $kp_internal_id . '/');
        $parser->cache = false;
        $model = new Film();
        $model->importFromParser($parser);
        $parser->clearContent();
        if($model->hasErrors()) {
            return null;
        }
        return $model;
    }
}
