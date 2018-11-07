<?php
namespace app\models\search;

use app\helpers\Translit;
use app\models\Country;
use app\models\FilmPerson;
use app\models\Genre;
use app\models\Person;
use app\models\TopList;
use app\models\Torrent;
use Yii;
use app\models\Film;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class FilmSearch extends Film
{
    public $genre_id = [];

    public $country_id = [];

    public $person_id = [];

    public $actor_id = [];

    public $director_id = [];

    public $top_list_id = [];

    public $hide_watched = null;

    public $user_id;

    public $min_votes;

    public $term;

    public function formName()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'genre_id' => 'Жанр',
            'country_id' => 'Страна',
            'person_id' => 'Персона',
            'actor_id' => 'Актер',
            'director_id' => 'Режиссер',
        ]);
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[
                'max_quality', 'is_series', 'status', 'name', 'genre_id', 'country_id', 'year', 'person_id', 'kp_mark', 'imdb_mark',
                'critic_rating', 'user_review_rating', 'hide_watched', 'top_list_id', 'min_votes', 'actor_id', 'director_id'
            ], 'safe'],
        ];
    }

    /**
     * @param $ids
     * @return array
     */
    protected function getWithIds($ids)
    {
        return array_filter($ids, function($value){
            return $value > 0;
        });
    }

    /**
     * @param $ids
     * @return array
     */
    protected function getWithoutIds($ids)
    {
        $without_ids = array_filter($ids, function($value){
            return $value < 0;
        });
        return array_map(function($value){
            return abs($value);
        }, $without_ids);
    }

    public function search($params = [])
    {
        $query = Film::find();
        $query->from(['t' => self::tableName()]);
        $query->with(['genres', 'directors', 'actors', 'operators', 'screenwriters', 'producers', 'countries']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 50
        ]);

        $this->load($params);

        $query->leftJoin('{{%currency}} cur', 'cur.iso = t.budget_currency');

        if($this->term) {
            $terms = preg_split('#[^0-9a-zа-я]#ui', $this->term);
            $terms = array_filter($terms, function($value){
                return mb_strlen($value, 'utf-8') > 2;
            });
            foreach($terms as $term) {
                $query->andWhere(['or', ['like', 't.original_name', $term], ['like', 't.name', $term]]);
            }
        }

        $query->andFilterWhere(['like', 't.name', $this->name]);

        $query->andFilterWhere(['t.status' => $this->status]);

        if($this->genre_id) {
            $with_ids = $this->getWithIds($this->genre_id);
            $without_ids = $this->getWithoutIds($this->genre_id);
            if($with_ids) {
                $query->leftJoin('{{%film_genre}} fg', 'fg.film_id = t.id');
                $query->andFilterWhere(['in', 'fg.genre_id', $with_ids]);
            }
            if($without_ids) {
                $query->leftJoin('{{%film_genre}} fwg', 'fwg.film_id = t.id AND fwg.genre_id IN('.implode(',', $without_ids).')');
                $query->andWhere('fwg.genre_id IS NULL');
            }
        }

        if($this->country_id) {
            $with_ids = $this->getWithIds($this->country_id);
            $without_ids = $this->getWithoutIds($this->country_id);
            if($with_ids) {
                $query->leftJoin('{{%film_country}} fc', 'fc.film_id = t.id');
                $query->andFilterWhere(['in', 'fc.country_id', $with_ids]);
            }
            if($without_ids) {
                $query->leftJoin('{{%film_country}} fwc', 'fwc.film_id = t.id AND fwc.country_id IN('.implode(',', $without_ids).')');
                $query->andWhere('fwc.country_id IS NULL');
            }
        }

        if($this->person_id) {
            $with_ids = $this->getWithIds($this->person_id);
            $without_ids = $this->getWithoutIds($this->person_id);
            if($with_ids) {
                $query->leftJoin('{{%film_person}} fp', 'fp.film_id = t.id');
                $query->andFilterWhere(['in', 'fp.person_id', $with_ids]);
                $query->andFilterWhere(['fp.role' => [FilmPerson::ROLE_DIRECTOR, FilmPerson::ROLE_ACTOR]]);
            }
            if($without_ids) {
                $sql = 'fwp.film_id = t.id AND fwp.person_id IN('.implode(',', $without_ids).') AND fwp.role IN("director", "actor")';
                $query->leftJoin('{{%film_person}} fwp', $sql);
                $query->andWhere('fwp.person_id IS NULL');
            }
        }

        if($this->actor_id) {
            $with_ids = $this->getWithIds($this->actor_id);
            $without_ids = $this->getWithoutIds($this->actor_id);
            if($with_ids) {
                $query->leftJoin('{{%film_person}} actor', 'actor.film_id = t.id AND actor.role = :actor_role', ['actor_role' => FilmPerson::ROLE_ACTOR]);
                $query->andFilterWhere(['in', 'actor.person_id', $with_ids]);
            }
            if($without_ids) {
                $sql = 'wActor.film_id = t.id AND wActor.person_id IN('.implode(',', $without_ids).') AND wActor.role = "actor"';
                $query->leftJoin('{{%film_person}} wActor', $sql);
                $query->andWhere('wActor.person_id IS NULL');
            }
        }

        if($this->director_id) {
            $with_ids = $this->getWithIds($this->director_id);
            $without_ids = $this->getWithoutIds($this->director_id);
            if($with_ids) {
                $query->leftJoin('{{%film_person}} director', 'director.film_id = t.id AND director.role = :director_role', ['director_role' => FilmPerson::ROLE_DIRECTOR]);
                $query->andFilterWhere(['in', 'director.person_id', $with_ids]);
            }
            if($without_ids) {
                $sql = 'wDirector.film_id = t.id AND wDirector.person_id IN('.implode(',', $without_ids).') AND wDirector.role = "director"';
                $query->leftJoin('{{%film_person}} wDirector', $sql);
                $query->andWhere('wDirector.person_id IS NULL');
            }
        }

        if($this->top_list_id) {
            $with_ids = $this->getWithIds($this->top_list_id);
            $without_ids = $this->getWithoutIds($this->top_list_id);
            if($with_ids) {
                $query->leftJoin('{{%film_top_list}} ftl', 'ftl.film_id = t.id');
                $query->andFilterWhere(['in', 'ftl.top_list_id', $with_ids]);
            }
            if($without_ids) {
                $query->leftJoin('{{%film_top_list}} fwtl', 'fwtl.film_id = t.id AND fwtl.top_list_id IN('.implode(',', $without_ids).')');
                $query->andWhere('fwtl.top_list_id IS NULL');
            }
        }

        if($this->hide_watched) {
            $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id AND fm.user_id = :user_id', [
                'user_id' => $this->user_id,
            ]);
            $query->andWhere('fm.user_id IS NULL');
        }

        if($this->year) {
            list($year_from, $year_till) = explode(';', $this->year);
            $query->andFilterWhere(['>=', 't.year', $year_from]);
            $query->andFilterWhere(['<=', 't.year', $year_till]);
        }

        $query->andFilterWhere(['>=', 't.max_quality', $this->max_quality]);

        if($this->kp_mark) {
            list($value_from, $value_till) = explode(';', $this->kp_mark);
            if($value_from != 1) {
                $query->andFilterWhere(['>=', 't.kp_mark', $value_from]);
            }
            if($value_till != 10) {
                $query->andFilterWhere(['<=', 't.kp_mark', $value_till]);
            }
            /*if($this->kp_mark != '1;10') {}*/
        }

        if($this->min_votes) {
            $query->andWhere([
                'or',
                ['>=', 't.kp_mark_votes', $this->min_votes],
                ['>=', 't.imdb_mark_votes', $this->min_votes]
            ]);
        }

        if($this->imdb_mark) {
            list($value_from, $value_till) = explode(';', $this->imdb_mark);
            if($value_from != 1) {
                $query->andFilterWhere(['>=', 't.imdb_mark', $value_from]);
            }
            if($value_till != 10) {
                $query->andFilterWhere(['<=', 't.imdb_mark', $value_till]);
            }
            /*if($this->imdb_mark != '1;10') {}*/
        }

        if($this->critic_rating) {
            list($value_from, $value_till) = explode(';', $this->critic_rating);
            if($value_from != 0) {
                $query->andFilterWhere(['>=', 't.critic_rating', $value_from]);
            }
            if($value_till != 100) {
                $query->andFilterWhere(['<=', 't.critic_rating', $value_till]);
            }
        }

        if($this->user_review_rating) {
            list($value_from, $value_till) = explode(';', $this->user_review_rating);
            if($value_from != 0) {
                $query->andFilterWhere(['>=', 't.user_review_rating', $value_from]);
            }
            if($value_till != 100) {
                $query->andFilterWhere(['<=', 't.user_review_rating', $value_till]);
            }
        }

        if($this->is_series !== null) {
            $query->andWhere(['is_series' => $this->is_series]);
        }

        $query->groupBy('t.id');

        $dataProvider->setSort([
            'defaultOrder' => ['last_torrent_at' => SORT_DESC],
            'attributes' => [
                'premiere' => [
                    'default' => SORT_DESC,
                    'label' => 'премьере',
                ],
                'imdb_mark' => [
                    'default' => SORT_DESC,
                    'label' => 'IMDb',
                ],
                'critic_rating' => [
                    'default' => SORT_DESC,
                    'label' => 'рейт. критиков',
                ],
                'kp_mark' => [
                    'default' => SORT_DESC,
                    'label' => 'КиноПоиск',
                ],
                'user_review_rating' => [
                    'default' => SORT_DESC,
                    'label' => 'рейт. рецензий',
                ],
                'name' => [
                    'default' => SORT_ASC,
                    'label' => 'названию',
                ],
                'last_torrent_at' => [
                    'default' => SORT_DESC,
                    'label' => 'на торрентах',
                ],
                'budget' => [
                    'default' => SORT_DESC,
                    'label' => 'бюджету',
                ],
            ],
        ]);

        return $dataProvider;
    }

    /**
     * @return array
     */
    public function getMaxQualityList()
    {
        return [
            Torrent::QUALITY_BLU_RAY => 'BluRay',
            Torrent::QUALITY_HD_RIP => 'HDRip и выше',
            Torrent::QUALITY_DVD_RIP => 'DVDRip и выше',
            Torrent::QUALITY_TV_RIP => 'TVRip и выше',
            Torrent::QUALITY_DVD_SCR => 'DVDScr и выше',
            Torrent::QUALITY_TS => 'CAMRip и выше',
        ];
    }

    /**
     * @return Genre[]
     */
    public function getGenres()
    {
        if(empty($this->genre_id)) {
            return [];
        }
        $genre_ids = array_map(function($value){
            return abs($value);
        }, $this->genre_id);
        $models = Genre::findAll(['id' => $genre_ids]);
        /** @var Genre $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->genre_id);
        }
        return $models;
    }

    /**
     * @return Country[]
     */
    public function getCountries()
    {
        if(empty($this->country_id)) {
            return [];
        }
        $country_ids = array_map(function($value){
            return abs($value);
        }, $this->country_id);
        $models = Country::findAll(['id' => $country_ids]);
        /** @var Country $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->country_id);
        }
        return $models;
    }

    /**
     * @return TopList[]
     */
    public function getTopLists()
    {
        if(empty($this->top_list_id)) {
            return [];
        }
        $top_list_ids = array_map(function($value){
            return abs($value);
        }, $this->top_list_id);
        $models = TopList::findAll(['id' => $top_list_ids]);
        /** @var TopList $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->top_list_id);
        }
        return $models;
    }

    /**
     * @return Person[]
     */
    public function getPersons()
    {
        if(empty($this->person_id)) {
            return [];
        }
        $person_ids = array_map(function($value){
            return abs($value);
        }, $this->person_id);
        $models = Person::findAll(['id' => $person_ids]);
        /** @var Person $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->person_id);
        }
        return $models;
    }

    /**
     * @return Person[]
     */
    public function getActors()
    {
        if(empty($this->actor_id)) {
            return [];
        }
        $actor_ids = array_map(function($value){
            return abs($value);
        }, $this->actor_id);
        $models = Person::findAll(['id' => $actor_ids]);
        /** @var Person $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->actor_id);
        }
        return $models;
    }

    /**
     * @return Person[]
     */
    public function getDirectors()
    {
        if(empty($this->director_id)) {
            return [];
        }
        $director_ids = array_map(function($value){
            return abs($value);
        }, $this->director_id);
        $models = Person::findAll(['id' => $director_ids]);
        /** @var Person $model */
        foreach($models as $model) {
            $model->without_it = !in_array($model->id, $this->director_id);
        }
        return $models;
    }
}