<?php
namespace app\models;

use Yii;
use Exception;
use Zend\Http;

/**
 * This is the model class for table "tbl_proxy".
 *
 * @property integer $id
 * @property string $ip
 * @property integer $port
 * @property integer $user
 * @property integer $pass
 * @property integer $speed
 * @property integer $bad_request
 * @property integer $total_request
 * @property integer $reliability
 * @property integer $created_at
 * @property integer $updated_at
 */
class Proxy extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%proxy}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip', 'port'], 'required'],
            [['speed', 'created_at', 'updated_at'], 'integer'],
            [['port'], 'integer', 'max' => 999999],
            [['ip'], 'string', 'max' => 16],
            ['ip', 'match', 'pattern' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'port' => 'Port',
            'speed' => 'Speed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return self
     */
    public static function rand()
    {
        return self::find()->orderBy('RAND()')->one();
    }

    /**
     * @param Http\Client $client
     */
    public function apply(Http\Client $client)
    {
        $adapter = new Http\Client\Adapter\Curl;
        $options = [
            'proxyhost' =>  $this->ip,
            'proxyport' => $this->port,
            //'timeout' => 3,
        ];
        if($this->user && $this->pass) {
            $options['proxyuser'] = $this->user;
            $options['proxypass'] = $this->pass;
        }
        $adapter->setOptions($options);
        //$adapter->setCurlOption(CURLOPT_CONNECTTIMEOUT, 3);
        $client->setAdapter($adapter);
    }

    /**
     * @param array $attributes
     * @return Proxy
     */
    public static function findOrCreate(array $attributes)
    {
        $model = self::findOne($attributes);
        if($model === null) {
            $model = new self;
            $model->setAttributes($attributes);
            $model->save();
        }
        return $model;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if($this->total_request) {
            $this->reliability = max(0, 100 - $this->bad_request / $this->total_request * 100);
        } else {
            $this->reliability = 0;
        }
        return parent::beforeSave($insert);
    }
}
