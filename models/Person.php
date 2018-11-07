<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%person}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $original_name
 * @property string $image_url
 * @property string $amazon_url
 * @property float $avg_mark
 * @property string $url
 * @property integer $kp_internal_id
 * @property integer $last_check_at
 * @property integer $new_check_at
 *
 * @property Film[] $directorFilms
 */
class Person extends ActiveRecord
{
    public $without_it;

    public $user_mark;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%person}}';
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'imageUrl',
            /*'image' => function(self $model){
                return [
                    'small' => Url::to(['person/image', 'id' => $model->id, 'square' => 1], true),
                    'full' => Url::to(['person/image', 'id' => $model->id], true),
                ];
            },*/
        ];
    }

    /**
     * @return ActiveQuery
     */
    /*public static function find()
    {
        return parent::find()->orderBy(['rating' => SORT_DESC]);
    }*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'kp_internal_id'], 'required'],
            [['kp_internal_id'], 'unique'],
            [['kp_internal_id'], 'integer'],
            [['name', 'original_name'], 'string', 'max' => 512],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'kp_internal_id' => 'Kp Internal ID',
        ];
    }

    /**
     * @param $data
     * @return Genre|static
     */
    public static function findOrCreate($data)
    {
        $kp_internal_id = ArrayHelper::remove($data, 'kp_internal_id');
        $model = self::findOne(['kp_internal_id' => $kp_internal_id]);
        if($model == null) {
            $model = new self;
            $model->kp_internal_id = $kp_internal_id;
            $model->setAttributes($data, false);
            $model->save(false);
        }
        return $model;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return Html::a($this->name, $this->url, ['target' => '_blank']);
    }

    public function getUrl()
    {
        return "https://www.kinopoisk.ru/name/{$this->kp_internal_id}/";
    }

    /**
     * @return ActiveQuery
     */
    public function getDirectorFilms()
    {
        return $this->hasMany(Film::className(), ['id' => 'film_id'])
            ->viaTable('{{%film_person}}', ['person_id' => 'id'], function(ActiveQuery $relation){
                $relation->andWhere(['role' => FilmPerson::ROLE_DIRECTOR]);
            });
    }

    /**
     * @return ActiveQuery
     */
    public function getFilms()
    {
        return $this->hasMany(Film::className(), ['id' => 'film_id'])
            ->viaTable('{{%film_person}}', ['person_id' => 'id']);
    }

    public function generateAvgMark()
    {
        return $this->getFilms()->select('AVG(imdb_mark)')->andWhere('imdb_mark_votes > 500')->scalar();
    }

    /**
     * @param $user_id
     * @return bool
     */
    public function inFavourite($user_id)
    {
        return PersonFavourite::find()->andWhere(['person_id' => $this->id, 'user_id' => $user_id])->exists();
    }

    public function getImageUrl()
    {
        if($this->amazon_url) {
            return $this->amazon_url;
        }
        return Url::to(['person/image', 'id' => $this->id], true);
    }

    public function getDirectorLink()
    {
        return Html::a($this->name, ['film/index', 'director_id' => [$this->id], 'sort' => '-premiere']);
    }

    public function getActorLink()
    {
        return Html::a($this->name, ['film/index', 'director_id' => [$this->id], 'sort' => '-premiere']);
    }

    public function getDirectorText($without_ids = [])
    {
        if(user()->isGuest) {
            return Html::a($this->name, ['film/index', 'director_id' => [$this->id], 'sort' => '-premiere']);
        }

        $inFavourite = $this->inFavourite(user()->id);
        $link = Html::a($this->name, ['film/index', 'director_id' => [$this->id], 'sort' => '-premiere'], ['style' => $inFavourite ? 'color: #19b735;font-weight: 600;' : null]);

        $query = (new Query)->from(['t' => Film::tableName()]);
        $query->select(['mark' => 'fm.mark', 'name' => 't.name']);
        $query->leftJoin('{{%film_person}} fp', 'fp.film_id = t.id AND fp.role = :role', ['role' => FilmPerson::ROLE_DIRECTOR]);
        $query->leftJoin('{{%film_mark}} fm', 'fm.film_id = t.id');
        $query->andWhere(['fm.user_id' => user()->id]);
        $query->andWhere(['fp.person_id' => $this->id]);
        $query->andFilterWhere(['not in', 'id', $without_ids]);
        $query->orderBy(['fm.mark' => SORT_DESC]);
        $query->limit(3);
        $rows = $query->all();

        if(count($rows)) {
            return $link . ' - <span style="color:#8e8e8e">' . implode(', ', ArrayHelper::getColumn($rows, 'name')) . '</span>';
        }
        return $link;
    }
}
