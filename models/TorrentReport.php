<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class TorrentReport extends Model
{
    public $start_at;

    public $end_at;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    public function formName()
    {
        return '';
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function getRows()
    {
        $query = Torrent::find()->select(['date' => "FROM_UNIXTIME(created_at, '%Y-%m-%d')", 'count' => "COUNT(*)"])
            ->groupBy('date')
            ->orderBy(['created_at' => SORT_ASC])
            ->andWhere(['>=', 'created_at', $this->start_at])
            ->andWhere(['<=', 'created_at', $this->end_at])
            ->asArray()
            ->indexBy('date');

        $rows = $query->all();

        return $rows;
    }

    public function getLabels()
    {
        $list = [];
        for($time = $this->start_at; $time < $this->end_at; $time += 3600 * 24) {
            $list[] = Yii::$app->formatter->asDate($time, 'dd MMM');
        }
        return $list;
    }

    public function getData()
    {
        $rows = $this->getRows();
        $list = [];
        for($time = $this->start_at; $time < $this->end_at; $time += 3600 * 24) {
            $key = date('Y-m-d', $time);
            $list[] = ArrayHelper::getValue($rows, "$key.count", 0);
        }
        return $list;
    }

    /**
     * @return array
     */
    public function getChartOptions()
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $this->getLabels(),
                'datasets' => [
                    [
                        'label' => 'Количество',
                        'data' => $this->getData(),
                        'backgroundColor' => 'rgba(53,152,220,0.5)',
                        'fill' => true
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'legend' => ['display' => false],
                'scales' => [
                    'yAxes' => [
                        [
                            'display' => true,
                            'scaleLabel' => [
                                'display' => true,
                                'labelString' => 'Количество',
                            ],
                            'ticks' => [
                                'suggestedMin' => 0,
                                'beginAtZero' => true,
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }
}