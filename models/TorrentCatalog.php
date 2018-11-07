<?php
namespace app\models;

use app\components\Parser;
use app\components\ParserIterator;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%torrent_catalog}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $iterator_class
 * @property string $url
 * @property integer $count_pages
 * @property integer $new_check_at
 * @property integer $last_check_at
 * @property integer $check_interval
 * @property integer $count_total
 * @property integer $count_till_week
 * @property integer $count_till_month
 * @property integer $count_errors
 * @property integer $success_percent
 * @property integer $tracker
 * @property integer $is_series
 *
 * @property Torrent[] $torrents
 */
class TorrentCatalog extends ActiveRecord
{
    const TRACKER_NNM_CLUB = 1;
    const TRACKER_RUTRACKER = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%torrent_catalog}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'iterator_class' => 'Iterator Class',
            'url' => 'Url',
            'count_pages' => 'Кол. страниц',
            'new_check_at' => 'Следующая проверка',
            'last_check_at' => 'Последняя проверка',
            'check_interval' => 'Интервал',
        ];
    }

    /**
     * @return Parser[]
     */
    public function getParsers()
    {
        $className = $this->iterator_class;

        return new $className($this->url);
    }

    /**
     * @return ParserIterator
     */
    public function getIterator()
    {
        $className = $this->iterator_class;

        return new $className($this->url);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTorrents()
    {
        return $this->hasMany(Torrent::className(), ['catalog_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTorrentErrors()
    {
        return $this->hasMany(TorrentError::className(), ['catalog_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getTrackerList()
    {
        return [
            self::TRACKER_NNM_CLUB => 'nnm-club.me',
            self::TRACKER_RUTRACKER => 'rutracker.org',
        ];
    }

    /**
     * @return mixed
     */
    public function getTrackerName()
    {
        return ArrayHelper::getValue($this->getTrackerList(), $this->tracker);
    }

    public function getParsingLink()
    {
        return Html::a($this->name, ['torrent-catalog/check-torrent', 'id' => $this->id], ['target' => '_blank']);
    }

    public function getViewLink()
    {
        return Html::a('<i class="fa fa-share-square-o"></i>', $this->getViewUrl(), ['target' => '_blank']);
    }

    public function getViewUrl()
    {
        return $this->url;
    }

    public function updateCache()
    {
        $this->count_total = $this->getTorrents()->count();
        $this->count_till_week = $this->getTorrents()->andWhere(['>=', 'created_at', time() - 3600*24*7])->count();
        $this->count_till_month = $this->getTorrents()->andWhere(['>=', 'created_at', time() - 3600*24*31])->count();
        $this->count_errors = $this->getTorrentErrors()->count();
        if($this->count_total) {
            $this->success_percent = round($this->count_total / ($this->count_total + $this->count_errors) * 100);
        } else {
            $this->success_percent = 100;
        }
        $this->save(false);
    }

    public function generateCheckInterval()
    {
        if($this->count_till_month > 100) {
            return 1;
        }
        if($this->count_till_month > 50) {
            return 3;
        }
        if($this->count_till_month > 20) {
            return 6;
        }
        if($this->count_till_month > 10) {
            return 24;
        }
        if($this->count_till_month > 0) {
            return 24*4;
        }
        return 24*7;
    }
}