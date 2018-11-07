<?php
namespace app\models\search;

use app\models\FilmMark;
use app\models\FilmWanted;
use Yii;
use app\models\Country;
use app\models\FilmPerson;
use app\models\Genre;
use app\models\Person;
use app\models\TopList;
use app\models\Torrent;
use app\models\Film;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class FilmApiSearch extends Film
{
    const WATCH_VIEW  = 1;
    const WATCH_NOT_VIEW  = 2;
    const WATCH_WANT_VIEW  = 3;

    public $type;

    public $genre_ids = [];

    public $no_genre_ids = [];

    public $country_ids = [];

    public $no_country_ids = [];

    public $person_ids = [];

    public $actor_ids = [];

    public $director_ids = [];

    public $screenwriter_ids = [];

    public $operator_ids = [];

    public $producer_ids = [];

    public $hide_watched = null;

    public $user_id;

    public $min_votes;

    public $term;

    public $mark;

    public $mark_votes;

    public $year_from;

    public $year_till;

    public $quality;

    public $watch;

    public function formName()
    {
        return '';
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
                'no_genre_ids', 'genre_ids',
                'country_ids', 'no_country_ids',
                'person_ids', 'actor_ids', 'director_ids', 'screenwriter_ids', 'operator_ids', 'producer_ids',
                'type', 'mark', 'mark_votes',
                'year_from', 'year_till',
                'quality', 'watch',

                /*'max_quality', 'is_series', 'status', 'name', 'genre_id', 'country_id', 'year', 'person_id', 'kp_mark', ,
                'critic_rating', 'user_review_rating', 'hide_watched', 'top_list_id', 'min_votes', 'actor_id', 'director_id',*/
            ], 'safe'],
        ];
    }

    public function search($params = [])
    {
        $query = Film::find();
        $query->from(['t' => self::tableName()]);
        $query->with([
            'directors',
            'actors',
            'genres',
            'countries'
        ]);
        //$query->with(['directors', 'actors', 'producers', 'screenwriters', 'operators', 'genres', 'countries', 'seasons',]);
        //$query->with(['genres', 'directors', 'actors', 'operators', 'screenwriters', 'producers', 'countries']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 10
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

        if($this->genre_ids) {
            $query->leftJoin('{{%film_genre}} fg', 'fg.film_id = t.id');
            $query->andFilterWhere(['in', 'fg.genre_id', $this->genre_ids]);
        }
        if($this->no_genre_ids) {
            $noGenreIds = is_array($this->no_genre_ids) ? implode(',', $this->no_genre_ids) : (int)$this->no_genre_ids;
            $query->leftJoin('{{%film_genre}} fwg', 'fwg.film_id = t.id AND fwg.genre_id IN('.$noGenreIds.')');
            $query->andWhere('fwg.genre_id IS NULL');
        }
        if($this->country_ids) {
            $query->leftJoin('{{%film_country}} fc', 'fc.film_id = t.id');
            $query->andFilterWhere(['in', 'fc.country_id', $this->country_ids]);
        }
        if($this->no_country_ids) {
            $noCountryIds = is_array($this->no_country_ids) ? implode(',', $this->no_country_ids) : (int)$this->no_country_ids;
            $query->leftJoin('{{%film_country}} fwc', 'fwc.film_id = t.id AND fwc.country_id IN('.$noCountryIds.')');
            $query->andWhere('fwc.country_id IS NULL');
        }

        $this->applyPersonToQuery($query, $this->person_ids, [FilmPerson::ROLE_DIRECTOR, FilmPerson::ROLE_ACTOR, FilmPerson::ROLE_SCREENWRITER]);
        $this->applyPersonToQuery($query, $this->actor_ids, FilmPerson::ROLE_ACTOR);
        $this->applyPersonToQuery($query, $this->director_ids, FilmPerson::ROLE_DIRECTOR);
        $this->applyPersonToQuery($query, $this->screenwriter_ids, FilmPerson::ROLE_SCREENWRITER);
        $this->applyPersonToQuery($query, $this->operator_ids, FilmPerson::ROLE_OPERATOR);
        $this->applyPersonToQuery($query, $this->producer_ids, FilmPerson::ROLE_PRODUCER);

        if($this->quality) {
            $query->andWhere(['>=', 't.max_quality', $this->quality]);
        }

        if($year_from = (int)$this->year_from) {
            $query->andFilterWhere(['>=', 't.year', $year_from]);
        }

        if($year_till = (int)$this->year_till) {
            $query->andFilterWhere(['<=', 't.year', $year_till]);
        }

        if($mark = (float)$this->mark) {
            $query->andFilterWhere(['>=', 't.imdb_mark', $mark]);
        }
        if($mark_votes = (int)$this->mark_votes) {
            $query->andWhere(['>=', 't.imdb_mark_votes', $mark_votes]);
        }

        $query->andWhere([
            'is_series' => $this->type === 'series' ? 1 : 0
        ]);

        $query->groupBy('t.id');

        if($this->user_id) {
            if($this->watch == self::WATCH_NOT_VIEW) {
                $film_ids = FilmMark::find()->andWhere(['user_id' => $this->user_id])->select('film_id')->column();
                $query->andWhere(['not in', 't.id', $film_ids]);
            }
            if($this->watch == self::WATCH_VIEW) {
                $film_ids = FilmMark::find()->andWhere(['user_id' => $this->user_id])->select('film_id')->column();
                $query->andWhere(['t.id' => $film_ids]);
            }
            if($this->watch == self::WATCH_WANT_VIEW) {
                $film_ids = FilmWanted::find()->andWhere(['user_id' => $this->user_id])->select('film_id')->column();
                $query->andWhere(['t.id' => $film_ids]);
            }
        }

        $dataProvider->setSort($this->getSort());

        return $dataProvider;
    }

    /**
     * @param Query $query
     * @param $person_ids
     * @param $role
     */
    protected function applyPersonToQuery(Query $query, $person_ids, $role)
    {
        if(empty($person_ids)) {
            return;
        }
        $film_ids = FilmPerson::find()->andWhere([
            'person_id' => $person_ids,
            'role' => $role
        ])->select(['film_id'])->column();

        $query->andWhere(['t.id' => $film_ids]);
    }

    public function getSort()
    {
        return [
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
        ];
    }
}