<?php
namespace app\models;

/**
 * This is the model class for table "{{%series_season}}".
 *
 * @property integer $id
 * @property integer $film_id
 * @property integer $number
 * @property string $name
 * @property integer $count_episodes
 * @property integer $year
 *
 * @property SeriesEpisode[] $episodes
 */
class SeriesSeason extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%series_season}}';
    }

    public function extraFields()
    {
        return ['episodes'];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEpisodes()
    {
        return $this->hasMany(SeriesEpisode::className(), ['season_id' => 'id']);
    }
}