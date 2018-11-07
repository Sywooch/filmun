<?php
namespace app\models;

use app\components\Parser;
use Yii;
use yii\helpers\ArrayHelper;
use Zend\Http;

/**
 * This is the model class for table "{{%catalog}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property string $portal
 */
class Catalog extends ActiveRecord
{
    const PORTAL_NNMCLUB = 'nnmclub';
    const PORTAL_RUTRACKER = 'rutracker';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%catalog}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'portal'], 'required'],
            [['url'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 64]
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Заглавие',
            'url' => 'Url',
            'portal' => 'Portal',
        ];
    }

    /**
     * @return Parser[]
     */
    public function getParsers()
    {
        $className = ArrayHelper::getValue([
            self::PORTAL_RUTRACKER => 'app\components\RutrackerIterator',
            self::PORTAL_NNMCLUB => 'app\components\NnmClubIterator',
        ], $this->portal);

        return new $className($this->url);
    }
}
