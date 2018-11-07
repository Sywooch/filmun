<?php
namespace app\models;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%person}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $kp_url
 * @property string $description
 * @property string $preview
 * @property string $kp_preview
 * @property string $content
 * @property integer $public_at
 * @property integer $person_id
 * @property integer $film_id
 */
class News extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%news}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['public_at', 'person_id', 'film_id'], 'integer'],
            [['title', 'kp_url'], 'string', 'max' => 255],
            [['kp_url'], 'url'],
            [['description'], 'string'],
        ];
    }

    public function getImageUrl()
    {
        return Url::to(['news/image', 'url' => $this->kp_preview], true);
    }
}