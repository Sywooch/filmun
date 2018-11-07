<?php
namespace app\components;

use Yii;
use Aws\S3\S3Client;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class AmazonS3 extends Component
{
    public $bucket;

    public $region;

    public $key;

    public $secret;

    public $path = '';

    protected $_client;

    public function getClient()
    {
        if ($this->_client === null) {
            $this->_client = new S3Client([
                'version' => 'latest',
                'region'  => $this->region,
                'credentials' => [
                    'key'    => $this->key,
                    'secret' => $this->secret,
                ]
            ]);
        }
        return $this->_client;
    }

    public function putImage($file, $name)
    {
        return $this->put($file, $name, [
            'ContentType' => 'image/jpeg',
        ]);
    }

    public function put($file, $name, $params = [])
    {
        $params = ArrayHelper::merge([
            'ACL' => 'public-read',
        ], $params);
        $params['Bucket'] = $this->bucket;
        $params['Key'] = $this->path . $name;
        $params['Body'] = $file;
        $result = $this->getClient()->putObject($params);

        if(@$result['@metadata']['statusCode'] == 200) {
            return $result['ObjectURL'];
        } else {
            return null;
        }
    }
}