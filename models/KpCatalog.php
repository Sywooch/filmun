<?php
namespace app\models;

use Yii;
use app\components\KinopoiskParser;

/**
 * This is the model class for table "{{%kp_catalog}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $iterator_class
 * @property integer $new_check_at
 * @property integer $last_check_at
 * @property integer $check_interval
 * @property string $url
 */
class KpCatalog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%kp_catalog}}';
    }

    /**
     * @return KinopoiskParser[]
     */
    public function getParsers()
    {
        $className = $this->iterator_class;

        return new $className($this->url);
    }
}