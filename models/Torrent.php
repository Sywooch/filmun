<?php
namespace app\models;

use app\components\RutrackerParser;
use app\models\query\TorrentQuery;
use Yii;
use Zend\Http;
use yii\helpers\Html;
use app\components\TorrentParser;
use app\components\NnmClubParser;

/**
 * This is the model class for table "{{%torrent}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $url
 * @property string $name
 * @property string $original_name
 * @property integer $size
 * @property string $size_text
 * @property integer $seeders
 * @property string $quality_text
 * @property integer $quality
 * @property string $transfer_text
 * @property integer $transfer
 * @property string $advert_text
 * @property integer $has_advert
 * @property string $director
 * @property array $kp_internal_ids
 * @property array $imdb_internal_ids
 * @property integer $year
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $last_check_at
 * @property integer $new_check_at
 * @property integer $catalog_id
 * @property integer $tracker
 * @property integer $is_series
 *
 * @property TorrentCatalog $catalog
 */
class Torrent extends ActiveRecord
{
    const QUALITY_TS = 1;
    const QUALITY_DVD_SCR = 2;
    const QUALITY_TV_RIP = 3;
    const QUALITY_DVD_RIP = 4;
    const QUALITY_HD_RIP = 5;
    const QUALITY_BLU_RAY = 6;
    const QUALITY_NONE = 0;

    /**
     * @return array
     */
    public function typeArrayAttributes()
    {
        return ['kp_internal_ids', 'imdb_internal_ids'];
    }

    public function fields()
    {
        return [
            'id',
            'title',
            'has_advert',
            'url',
            'transfer_text',
            'transfer',
            'size',
            'created_at',
            'seeders',
        ];
    }

    /**
     * @inheritdoc
     * @return TorrentQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(TorrentQuery::className(), [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'url', 'size', 'quality', 'created_at'], 'required'],
            [['url'], 'unique'],
            [['year', 'seeders', 'quality', 'transfer', 'size', 'has_advert'], 'integer'],
            [['size_text'], 'string', 'max' => 64],
            [['season'], 'string', 'max' => 32],
            [['name', 'original_name', 'url'], 'string', 'max' => 255],
            [['kp_internal_ids', 'imdb_internal_ids'], 'safe'],
            [['title', 'director', 'quality_text', 'transfer_text', 'advert_text'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%torrent}}';
    }

    public function importFromParser(TorrentParser $parser)
    {
        $this->url = $parser->url;
        $this->setAttributes($parser->getAttributes());
        $this->kp_internal_ids = $parser->getKpInternalIds();
        $this->imdb_internal_ids = $parser->getImdbInternalIds();
        return $this->save();
    }

    public function getSizeDecorate()
    {
        if($this->size > 1024 * 1024) {
            return round($this->size/1024/1024, 1) . ' ТБ';
        } else if($this->size > 1024) {
            return round($this->size/1024, 1) . ' ГБ';
        } else {
            return $this->size . ' МБ';
        }
    }

    public function getTransferDecorate()
    {
        if(empty($this->transfer_text)) {
            return '';
        }
        $options = [];
        if($this->transfer >= 6) {
            Html::addCssStyle($options, 'color: #26C281');
        } else if($this->transfer == 5) {
            Html::addCssStyle($options, 'color: #4476b6');
        } else if($this->transfer >= 3) {
            Html::addCssStyle($options, 'color: #b38f01');
        } else {
            Html::addCssStyle($options, 'color: #E08283');
        }
        return Html::tag('span', $this->transfer_text, $options);
    }

    public function getQualityDecorate()
    {
        if(empty($this->quality_text)) {
            return '';
        }
        $options = [];
        if($this->quality >= 5) {
            Html::addCssStyle($options, 'color: #26C281');
        } else if($this->quality >= 3) {
            Html::addCssStyle($options, 'color: #4476b6');
        } else {
            Html::addCssStyle($options, 'color: #E08283');
        }
        return Html::tag('span', $this->quality_text, $options);
    }

    public function getCreatedDecorate()
    {
        $time = $this->created_at;
        if($time > mktime(0,0,0)) {
            return 'сегодня';
        }
        $passed = time() - $time;
        if($passed < 3600*24*31) {
            return Yii::t('app', '{n, plural, one{# день} few{# дня} many{# дней} other{# дня}}', [
                'n' => ceil($passed/3600/24)
            ]);
        }
        if($passed < 3600*24*30*12) {
            return Yii::$app->formatter->asDate($this->created_at, 'd MMM');
        }
        return Yii::$app->formatter->asDate($this->created_at, 'MMM y');
    }

    public static function checkSeeders($term)
    {
        $urls = [];

        $data = NnmClubParser::checkSeeds($term);
        $urls = array_merge($urls, array_keys($data));
        foreach($data as $url => $seeders) {
            Torrent::updateAll([
                'seeders' => $seeders,
                'seeders_check_at' => time(),
            ], ['url' => $url]);
        }
        $data = RutrackerParser::checkSeeds($term);
        $urls = array_merge($urls, array_keys($data));
        foreach($data as $url => $seeders) {
            Torrent::updateAll([
                'seeders' => $seeders,
                'seeders_check_at' => time(),
            ], ['url' => $url]);
        }

        return Torrent::find()
            ->andWhere(['url' => $urls])
            ->andWhere(['!=', 'seeders', null])
            ->indexBy('id')
            ->select('seeders')
            ->column();
    }

    public function downloadFile()
    {
        $parser = $this->getParser();
        return $parser->getTorrentFile();
    }

    public function getLink()
    {
        Html::a('<i class="fa fa-share-square-o"></i>', $this->url, ['target' => '_blank']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatalog()
    {
        return $this->hasOne(TorrentCatalog::className(), ['id' => 'catalog_id']);
    }

    /**
     * @return array
     */
    public function getQualityList()
    {
        return [
            self::QUALITY_BLU_RAY => 'BluRay',
            self::QUALITY_HD_RIP => 'HDRip',
            self::QUALITY_DVD_RIP => 'DVDRip',
            self::QUALITY_TV_RIP => 'TVRip',
            self::QUALITY_DVD_SCR => 'DVDScr',
            self::QUALITY_TS => 'CAMRip',
            self::QUALITY_NONE => 'Неизвестно',
        ];
    }

    /**
     * @return TorrentParser|null
     */
    public function getParser()
    {
        switch ($this->tracker) {
            case TorrentCatalog::TRACKER_NNM_CLUB:
                return new NnmClubParser($this->url);
            case TorrentCatalog::TRACKER_RUTRACKER:
                return new RutrackerParser($this->url);
        }
        return null;
    }

    /**
     * @param $seeders
     * @param $url
     */
    public static function updateSeeders($seeders, $url)
    {
        Torrent::updateAll([
            'seeders' => $seeders,
            'seeders_check_at' => time(),
        ], ['url' => $url]);
    }
}