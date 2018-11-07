<?php
namespace app\models\query;

use Yii;
use yii\db\ActiveQuery;

class TorrentQuery extends ActiveQuery
{
    /**
     * @param $value
     * @return $this
     */
    public function andSeason($value)
    {
        if($value) {
            $this->andWhere('FIND_IN_SET(:season, season)', ['season' => $value]);
        }
        return $this;
    }
}