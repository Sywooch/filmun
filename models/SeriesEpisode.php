<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%series_episode}}".
 *
 * @property integer $id
 * @property integer $film_id
 * @property integer $season_id
 * @property integer $number
 * @property string $name
 * @property string $original_name
 * @property integer $premiere
 *
 * @property Film $film
 * @property SeriesSeason $season
 */
class SeriesEpisode extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%series_episode}}';
    }

    public function getFilm()
    {
        return $this->hasOne(Film::className(), ['id' => 'film_id']);
    }

    public function getSeason()
    {
        return $this->hasOne(SeriesSeason::className(), ['id' => 'season_id']);
    }

    public function getFullName()
    {
        return $this->original_name ? $this->name . ' / ' . $this->original_name : $this->name;
    }

    public function getPremiereDecorate()
    {
        $time = $this->premiere;
        if($time < time()) {
            return Yii::$app->formatter->asDate($this->premiere, 'd MMM');
        }
        if($time < mktime(0,0,0) + 3600*24) {
            return 'сегодня';
        }
        $left = $time - time();
        if($left < 3600*24*31) {
            return Yii::t('app', '{n, plural, one{# день} few{# дня} many{# дней} other{# дня}}', [
                'n' => ceil($left/3600/24)
            ]);
        }
        if($left < 3600*24*30*3) {
            return Yii::$app->formatter->asDate($this->premiere, 'd MMM');
        }
        return Yii::$app->formatter->asDate($this->premiere, 'MMM y');
    }
}