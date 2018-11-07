<?php
use yii\helpers\Json;
use app\models\Film;

$this->title = 'Статистика';

app\assets\ChartJsAsset::register($this);

$data = [];
$labels = [];

for($year = 1980; $year <= 2017; $year++) {
    $labels[] = $year;
    $data[] = Film::find()->andWhere('imdb_internal_id IS NOT NULL AND (_imdb_mark_votes - imdb_mark_votes) > 1000 AND imdb_mark_votes < 2000')->andWhere(['year' => $year])->count();
}

$options = [
    'type' => 'bar',
    'data' => [
        'labels' => $labels,
        'datasets' => [
            [
                'label' => 'Количество',
                'data' => $data,
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

$options = Json::encode($options);
$this->registerJs("
    new Chart($('#chart canvas'), $options);
");
?>

<div id="chart">
    <canvas width="100%" height="250px"></canvas>
</div>
