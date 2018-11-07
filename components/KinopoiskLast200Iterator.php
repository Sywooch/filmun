<?php
namespace app\components;

use Yii;

class KinopoiskLast200Iterator extends KinopoiskIterator
{
    /**
     * @return float|int
     */
    public function getTotalPages()
    {
        return 1;
    }
}