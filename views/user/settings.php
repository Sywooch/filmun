<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $user app\models\User */
/* @var $passwordForm app\models\ChangePasswordForm */
/* @var $kpSettingForm app\models\KpSettingForm */
/* @var $userSettingForm app\models\UserSettingForm */

$this->title = 'Настройка';
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs(
<<<JS

var checkAuth = false;
setInterval(function(){
    if(checkAuth === false) {
        return;
    }
    
    $.get('telegram/auth', function(json){
        if(json.success) {
            location.reload();
        }
    });
}, 1000);

$('#auth-telegram').on('click', function(){
    checkAuth = true;
    var top = Math.round(($(window).height() - 200) / 2);
    var left = Math.round(($(window).width() - 200) / 2);
    window.open("https://t.me/filmun_bot?start={$user->id}", "Telegram", "width=200,height=200,top=" + top + ",left=" + left);
    return false;
})

JS
);
?>
<div class="user-setting">
    <div class="row">
        <div class="col-lg-7">
            <?php if($message = Yii::$app->session->getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <h2 style="margin: 0 0 10px 0">Личные настройки</h2>

            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($userSettingForm, 'notify_torrent_quality')->dropDownList($userSettingForm->getNotifyTorrentQualityList(), [
                'prompt' => 'Не оповещать'
            ]) ?>

            <?= $form->field($userSettingForm, 'notify_torrent_transfer')->dropDownList($userSettingForm->getNotifyTorrentTransfer()) ?>

            <?= $form->field($userSettingForm, 'desired_film_size')->dropDownList($userSettingForm->getDesiredFilmSize()) ?>

            <?= $form->field($userSettingForm, 'email')->textInput() ?>

            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>

            <hr>

            <h2 style="margin: 0 0 10px 0">Оповещения telegram</h2>

            <?php if($user->telegram_id): ?>

                <a href="<?= \yii\helpers\Url::to(['telegram/logout']) ?>" class="btn btn-danger"><i class="fa fa-times"></i> Отключить оповещения telegram</a>

            <?php else: ?>

                <a href="#" class="btn btn-success" id="auth-telegram"><i class="fa fa-check"></i> Включить оповещения telegram</a>

            <?php endif; ?>

            <hr>

            <h2 style="margin: 0 0 10px 0">Интеграция с КиноПоиск</h2>

                <?php $form = ActiveForm::begin(); ?>

                <?= $form->field($kpSettingForm, 'kp_login')->textInput(['maxlength' => 32]) ?>

                <?= $form->field($kpSettingForm, 'kp_password')->passwordInput(['maxlength' => 32]) ?>

                <div class="form-group">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>

                    <?php if($user->kp_login && $user->kp_password): ?>
                    <a href="<?= \yii\helpers\Url::to(['user/import-from-kp']) ?>" target="_blank" class="btn btn-success pull-right" style="display: none">
                        Импортировать оценки и т.д. с кинопоиска
                    </a>
                    <?php endif; ?>
                </div>

                <?php ActiveForm::end(); ?>

            <hr>

            <h2 style="margin: 0 0 10px 0">Безопасность</h2>

            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($passwordForm, 'password')->passwordInput(['maxlength' => 18]) ?>

            <?= $form->field($passwordForm, 'password_repeat')->passwordInput(['maxlength' => 18]) ?>

            <div class="form-group">
                <?= Html::submitButton('Изменить пароль', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>