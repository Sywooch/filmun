<?php
namespace app\models;

use Yii;

/**
 * UserRss model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $link
 * @property string $description
 * @property integer $created_at
 */
class UserRss extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_rss}}';
    }
}