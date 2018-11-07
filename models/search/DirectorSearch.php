<?php
namespace app\models\search;

use app\models\FilmPerson;
use app\models\PersonFavourite;
use Yii;
use app\models\Person;
use yii\data\ActiveDataProvider;

class DirectorSearch extends Person
{
    public $user_id;

    public $hide_favourite = null;

    public $avg_mark;

    public $user_mark;

    public function formName()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hide_favourite', 'avg_mark', 'user_mark'], 'safe'],
        ];
    }

    public function search($params = [])
    {
        $this->load($params);

        $query = Person::find();
        $query->orderBy(false);
        $query->select([
            'id' => 't.id', 'name' => 't.name', 'original_name' => 't.original_name', 'kp_internal_id' => 't.kp_internal_id',
            'image_url' => 't.image_url', 'rating', 'user_mark' => 'AVG(fm.mark)', 'count_films' => 'COUNT(fm.film_id)'
        ]);
        $query->from(['t' => Person::tableName()]);

        $query->join('JOIN', '{{%film_person}} fp', "fp.person_id = t.id AND fp.role = :role", [
            'role' => FilmPerson::ROLE_DIRECTOR
        ]);

        $query->join('JOIN', '{{%film_mark}} fm', "fm.film_id = fp.film_id AND user_id = :user_id", [
            'user_id' => $this->user_id,
        ]);

        if($this->avg_mark) {
            $query->andWhere(['>=', 't.avg_mark', $this->avg_mark]);
        }

        if($this->user_mark) {
            $query->andHaving(['>=', 'user_mark', $this->user_mark]);
        }

        if($this->hide_favourite) {
            $favourite_ids = PersonFavourite::find()->select('person_id')->andWhere(['user_id' => $this->user_id])->column();
            $query->andWhere(['not in', 't.id', $favourite_ids]);
        }

        $query->groupBy('t.id');

        //$query->andHaving(['>', 'count_films', 2]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setPagination([
            'defaultPageSize' => 30
        ]);

        //$query->andFilterWhere(['like', 't.name', $this->name]);

        $dataProvider->setSort([
            'defaultOrder' => ['user_mark' => SORT_DESC],
            'attributes' => [
                'avg_mark' => [
                    'default' => SORT_DESC,
                    'label' => 'по общей оценке',
                    'asc' => ['t.avg_mark' => SORT_ASC, 't.rating' => SORT_DESC],
                    'desc' => ['t.avg_mark' => SORT_DESC, 't.rating' => SORT_DESC],
                ],
                'user_mark' => [
                    'default' => SORT_DESC,
                    'label' => 'по моей оценке',
                    'asc' => ['user_mark' => SORT_ASC, 't.rating' => SORT_DESC],
                    'desc' => ['user_mark' => SORT_DESC, 't.rating' => SORT_DESC],
                ],
                'count_films' => [
                    'default' => SORT_DESC,
                    'label' => 'по кол. фильмов',
                    'asc' => ['count_films' => SORT_ASC, 't.rating' => SORT_DESC],
                    'desc' => ['count_films' => SORT_DESC, 't.rating' => SORT_DESC],
                ],
            ],
        ]);

        return $dataProvider;
    }

    public function getAvgMarkList()
    {
        return [
            5 => 'Общая оценка >= 5',
            6 => 'Общая оценка >= 6',
            7 => 'Общая оценка >= 7',
            8 => 'Общая оценка >= 8',
            9 => 'Общая оценка >= 9',
        ];
    }

    public function getUserMarkList()
    {
        return [
            5 => 'Моя оценка >= 5',
            6 => 'Моя оценка >= 6',
            7 => 'Моя оценка >= 7',
            8 => 'Моя оценка >= 8',
            9 => 'Моя оценка >= 9',
            10 => 'Моя оценка 10',
        ];
    }
}