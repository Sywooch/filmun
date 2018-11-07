<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;

yii\web\JqueryAsset::register($this);
yii\bootstrap\BootstrapAsset::register($this);


$this->title = 'Ужас аркхема';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link href="<?= Yii::getAlias('@web/favicon.png') ?>" rel="shortcut icon" type="image/png" />
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="content">

    <div class="form-group">
        <select id="modifier">
            <option value="0.3333333">Обычный</option>
            <option value="0.5">Благословлен</option>
            <option value="0.1666666">Проклят</option>
        </select>
    </div>

    <div class="form-group">
        <label>Кубиков</label>

        <div class="row-flex">
            <div  class="control col-flex-1" onclick="changeCount(-1);">
                ⇦
            </div>
            <div id="count" class="input">1</div>
            <div class="control col-flex-1" onclick="changeCount(1);">
                ⇨
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Сложность</label>

        <div class="row-flex">
            <div class="control col-flex-1" onclick="changeComplexity(-1);">
                ⇦
            </div>
            <div id="complexity" class="input">1</div>
            <div class="control col-flex-1" onclick="changeComplexity(1);">
                ⇨
            </div>
        </div>
    </div>

    <div class="result" id="result">
        100%
    </div>

</div>

<?php
$this->registerJs(<<<JS

window.changeCount = function(val)
{
    var count = parseInt($('#count').text());
    $('#count').text(Math.max(1,count + val));
    $('#count').change();
}

window.changeComplexity = function(val)
{
    var complexity = parseInt($('#complexity').text());
    $('#complexity').text(Math.max(1,complexity + val));
    $('#complexity').change();
}

function fact(n){
     var res = 1;
     while(n) res *= n--;
     return res;
}

var calcProbability = function(n, k, modifier){
    var variants = fact(n) / (fact(k) * fact(n-k));
    return Math.pow(modifier,k)*Math.pow(1-modifier,n-k)*variants;
}

setInterval(function(){
    var count = parseInt($('#count').text());
    var complexity = parseInt($('#complexity').text());
    var modifier = parseFloat($('#modifier').val());
    
    var result = 0;
    for(var i = count; i >= complexity; i--) {
        result+= calcProbability(count, i, modifier);
    }
    
    $('#result').text(Math.round(result*1000)/10 + '%');
}, 700)

$(window).on('resize', function(){
    $('.content').height($(window).height() - 40);
}).resize();

JS
);
?>

<style>
    .content {
        max-width: 375px;
        background: #ececec;
        padding: 20px;
        margin: 0 auto;
    }

    .control {
        height: 80px;
        background: #809fe6;
        color:white;
        font-size:56px;
        text-align: center;
        line-height: 80px;
        cursor: pointer;
    }

    .input {
        height: 80px;
        width: 80px;
        text-align: center;
        font-size: 42px;
        border: 2px solid #b9b9b9;
        line-height: 80px;
    }

    select {
        font-size: 28px;
        width: 100%;
        padding: 4px 6px;
    }
    input {
        font-size: 16px;
        width: 100%;
        padding: 5px 6px;
        border: 1px solid grey;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .result {
        padding: 20px 50px;
        text-align: center;
        font-size: 72px;
        background: #dedede;
        border: 1px solid #9e9e9e;
    }

    label {
        font-size: 14px;
        display: block;
        text-align: center;
    }

    .row-flex {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        width:100%;
    }
    .col-flex-1 { flex: 1; }
    .col-flex-2 { flex: 2; }
    .col-flex-3 { flex: 3; }
</style>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
