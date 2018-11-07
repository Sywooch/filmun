<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%torrent_error}}".
 *
 * @property integer $id
 * @property string $url
 * @property array $attributes
 * @property string $message
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $catalog_id
 * @property integer $tracker
 */
class TorrentError extends ActiveRecord
{
    /**
     * @return array
     */
    public function typeArrayAttributes()
    {
        return ['attributes'];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%torrent_error}}';
    }

    /**
     * @param $url
     * @param TorrentCatalog
     * @param $errors
     */
    public static function create($url, TorrentCatalog $catalog, $errors)
    {
        /** @var self $model */
        $model = self::findOne(['url' => $url]);
        if($model === null) {
            $model = new self;
            $model->url = $url;
            $model->created_at = time();
        }
        $model->updated_at = time();
        $model->catalog_id = $catalog->id;
        $model->tracker = $catalog->tracker;
        $model->attributes = array_keys($errors);
        $model->message = implode(', ', $errors);
        $model->save(false);
    }
}