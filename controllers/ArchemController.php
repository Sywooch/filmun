<?php
namespace app\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Controller;

class ArchemController extends Controller
{
    public function actionIndex()
    {
        $this->layout = false;
        return $this->render('index-vue');
    }

    public function actionIndexOld()
    {
        $this->layout = false;
        return $this->render('index');
    }


    public function actionCheck($count = 1, $complexity = 1, $modifier = 1)
    {
        echo '<form method="get">';
        echo Html::dropDownList('modifier', $modifier, [
            1 => 'Обычный',
            2 => 'Благословлен',
            3 => 'Проклят',
        ]);
        echo '<br>';
        echo Html::label('Кубиков');
        echo '<br>';
        echo Html::textInput('count', $count, ['placeholder' => 'Кубиков']);
        echo '<br>';
        echo Html::label('Сложность');
        echo '<br>';
        echo Html::textInput('complexity', $complexity, ['placeholder' => 'Сложность']);
        echo '<br>';
        echo '<button type="submit">Готово</button>';
        echo '</form>';

        $needTake = ArrayHelper::getValue([
            1 => [5,6],
            2 => [4,5,6],
            3 => [6],
        ], $modifier);
        $countSuccess = 0;
        $countTotal = 300000;
        for($i = 0; $i < $countTotal; $i++) {
            $tryCount = 0;
            for($n = 0; $n < $count; $n++) {
                $throw = rand(1, 6);
                if(in_array($throw, $needTake)) {
                    $tryCount++;
                }
            }
            if($tryCount >= $complexity) {
                $countSuccess++;
            }
        }

        echo '<h1>';
        echo round($countSuccess/$countTotal*100, 1) . '%';
        echo '</h1>';
    }
}