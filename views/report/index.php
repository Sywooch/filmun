<?php
use yii\helpers\Json;

/**
 * @var yii\web\View $this
 * @var array $chart
 * @var app\models\KinopoiskReport $kinopoiskReport
 * @var app\models\TorrentReport $torrentReport
 */

$this->title = 'Статистика';

app\assets\ChartJsAsset::register($this);

$kpOptions = Json::encode($kinopoiskReport->getChartOptions());
$torrentOptions = Json::encode($torrentReport->getChartOptions());
$this->registerJs("
    var ctx = document.getElementById('kp-chart-canvas');
    new Chart(ctx, $kpOptions);
    new Chart($('#torrent-chart canvas'), $torrentOptions);
");
?>

<div>
    <h3>КиноПоиск</h3>

    <canvas width="100%" height="250px" id="kp-chart-canvas"></canvas>
</div>

<div id="torrent-chart">
    <h3>Торренты</h3>

    <canvas width="100%" height="250px"></canvas>
</div>