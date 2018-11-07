<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;

yii\bootstrap\BootstrapAsset::register($this);

$this->title = 'Ужас аркхема';
$this->registerJsFile('https://npmcdn.com/vue/dist/vue.js', ['position' => yii\web\View::POS_HEAD]);
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

<div class="content" id="app">

    <div class="form-group">
        <ul class="form-modifier">
            <li v-for="(option, index) in modifierList" @click="modifier = option.value" :class="[option.className, {selected: modifier == option.value}]">{{ option.label }}</li>
        </ul>
    </div>

    <div class="form-group">
        <label>Кубиков</label>

        <div class="row-flex">
            <div  class="control col-flex-1" @click="changeCountThrow(-1)"> ⇦ </div>
            <div id="count" class="input">{{ countThrow }}</div>
            <div class="control col-flex-1" @click="changeCountThrow(1)"> ⇨ </div>
        </div>
    </div>

    <div class="form-group">
        <label>Сложность</label>

        <div class="row-flex">
            <div class="control col-flex-1" @click="changeComplexity(-1)"> ⇦ </div>
            <div id="complexity" class="input">{{ complexity }}</div>
            <div class="control col-flex-1" @click="changeComplexity(1)"> ⇨ </div>
        </div>
    </div>

    <div class="result" :class="{'result-low': result <= 33.34, 'result-high': result >= 66.67}">
        {{ result }} %
    </div>

</div>


<script>
    new Vue({
        el: '#app',
        data: {
            modifierList: [{label: '⚅', value: 1/6, className: 'modifier-low'}, {label: '⚄', value: 1/3, className: 'modifier-mid'}, {label: '⚃', value: 1/2, className: 'modifier-high'}],
            modifier: 1/3,
            countThrow: 1,
            complexity: 1
        },
        computed: {
            result: function(){
                var result = 0;
                for(var i = this.countThrow; i >= this.complexity; i--) {
                    result+= this.calcProbability(this.countThrow, i, this.modifier);
                }
                return Math.round(result*100);
            }
        },
        methods: {
            changeComplexity: function(value){
                this.complexity += value;
                this.complexity = Math.max(1, this.complexity);
            },
            changeCountThrow: function(value){
                this.countThrow += value;
                this.countThrow = Math.max(1, this.countThrow);
            },
            calcProbability: function(n, k, modifier){
                var variants = this.fact(n) / (this.fact(k) * this.fact(n-k));
                return Math.pow(modifier,k)*Math.pow(1-modifier,n-k)*variants;
            },
            fact: function (n){
                var res = 1;
                while(n) res *= n--;
                return res;
            }
        }
    })
</script>
<style>
    .content {
        max-width: 375px;
        background: #ececec;
        padding: 20px;
        margin: 0 auto;
    }

    .form-modifier {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
    }

    .form-modifier li {
        text-align: center;
        background: #dedede;
        border: 2px solid #9e9e9e;
        padding: 7px 0;
        margin: 0 5px;
        cursor: pointer;
        flex: 1;
        font-size: 54px;
        color: #5d5d5d;
    }

    .form-modifier li.selected {
        border: 2px solid #698ee2;
        background: #809fe6;
        color: #2e3a54;
    }

    .form-modifier li.modifier-low.selected {
        color: #ff4949;
        background: #ffebeb;
        border: 2px solid #ff5b5b;
    }

    .form-modifier li.modifier-mid.selected {
        border: 2px solid #698ee2;
        background: #809fe6;
        color: #2b3e69;
    }

    .form-modifier li.modifier-high.selected {
        color: #28a228;
        background: #e5ffe5;
        border: 2px solid #28a228;
    }

    .control {
        height: 80px;
        background: #809fe6;
        color: white;
        font-size: 56px;
        text-align: center;
        line-height: 72px;
        cursor: pointer;
        border: 2px solid #5478ca;
    }

    .control:hover {
        background: #96b2f1;
        border: 2px solid #698ee2;
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
        border: 2px solid #5478ca;
        color: #2c457d;
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
        padding: 20px 0;
        text-align: center;
        font-size: 72px;
        background: #dedede;
        border: 2px solid #9e9e9e;
    }

    .result-low {
        color: #ff4949;
        background: #ffebeb;
        border: 2px solid #ff5b5b;
    }

    .result-high {
        color: #28a228;
        background: #e5ffe5;
        border: 2px solid #28a228;
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
