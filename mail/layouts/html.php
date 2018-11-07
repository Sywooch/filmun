<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\MessageInterface the message being composed */
/* @var $content string main view render result */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <title><?= Html::encode($this->title) ?></title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i,900,900i&amp;subset=cyrillic-ext,latin-ext" rel="stylesheet">
    <style type="text/css">
        * {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            box-sizing: border-box;
            font-size: 16px;
        }
        img {
            max-width: 100%;
        }
        body {
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
            width: 100% !important;
            height: 100%;
            line-height: 1.6em;
            background-color: #f1f1f1;
        }
        table td {
            vertical-align: top;
        }
        .body-wrap {
            background-color: #f1f1f1;
            width: 100%;
        }
        .container {
            display: block !important;
            max-width: 600px !important;
            margin: 0 auto !important;
            clear: both !important;
        }
        .content {
            max-width: 600px;
            margin: 0 auto;
            display: block;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .main {
            background-color: #fff;
        }
        .content-wrap {
            padding: 58px 48px 32px;
        }
        .content-block {
            padding: 0 0 16px;
        }
        .content-block-icon {
            color: #707070;
        }
        .content-block-icon img {
            margin-right: 12px;
            vertical-align: middle;
        }
        .content-block-footer {
            font-size: 14px;
            font-style: italic;
            color: #707070;
        }
        h1, h2, h3 {
            font-family: 'Roboto', sans-serif;
            color: #353535;
            margin: 40px 0 0;
            line-height: 1.2em;
            font-weight: 400;
        }
        h1 {
            font-size: 32px;
            font-weight: 500;
        }
        h2 {
            font-size: 24px;
        }
        h3 {
            font-size: 18px;
        }
        h4 {
            font-size: 16px;
            font-weight: 600;
            color: #353535;
        }
        p, ul, ol {
            margin-bottom: 10px;
            font-weight: normal;
        }
        p li, ul li, ol li {
            margin-left: 5px;
            list-style-position: inside;
        }
        a {
            color: #5291e0;
            text-decoration: underline;
        }
        .btn-primary {
            display: inline-block;
            color: #FFF;
            background-color: #50b9cd;
            border: solid #50b9cd;
            border-width: 8px 10px;
            font-size: 14px;
            text-align: center;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
        }
        .last {
            margin-bottom: 0;
        }
        .first {
            margin-top: 0;
        }
        .aligncenter {
            text-align: center;
        }
        .alignright {
            text-align: right;
        }
        .alignleft {
            text-align: left;
        }
        .clear {
            clear: both;
        }
        .logo {
            padding-bottom: 52px;
            text-align: center;
        }
        hr {
            border-top: none;
            border-color: #e5e5e5;
        }

        @media only screen and (max-width: 640px) {
            body {
                padding: 0 !important;
            }
            h1, h2, h3, h4 {
                font-weight: 800 !important;
                margin: 20px 0 5px !important;
            }
            h1 {
                font-size: 22px !important;
            }
            h2 {
                font-size: 18px !important;
            }
            h3 {
                font-size: 16px !important;
            }
            .container {
                padding: 0 !important;
                width: 100% !important;
            }
            .content {
                padding: 0 !important;
            }
            .content-wrap {
                padding: 10px !important;
            }
        }
    </style>
    <?php $this->head() ?>
</head>

<body itemscope itemtype="http://schema.org/EmailMessage">
<?php $this->beginBody() ?>
<table class="body-wrap">
    <tr>
        <td></td>
        <td class="container" width="600">
            <div class="content">
                <table class="main" width="100%" cellpadding="0" cellspacing="0" itemprop="action" itemscope itemtype="http://schema.org/ConfirmAction">
                    <tr>
                        <td class="content-wrap">
                            <meta itemprop="name" content="Confirm Email"/>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="content-block logo">
                                        <h1 style="margin: 0;">
                                            <img src="<?= $message->embed(Yii::getAlias('@app/mail/img/logo-filmun.png')) ?>" style="vertical-align: bottom">
                                            Фильмун
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $content ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block">
                                        <hr color="#e5e5e5" style="border-top: none">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block content-block-footer">
                                        <?= 'С уважением, серфис фильмун' ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div class="footer">
                    <table width="100%">
                        <tr>
                            <td class="aligncenter content-block"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </td>
        <td></td>
    </tr>
</table>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
