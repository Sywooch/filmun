<?php
/* @var $this yii\web\View */
/* @var $errors array */
?>
<table class="table table-hover">
    <?php foreach($errors as $error): ?>
    <tr>
        <td><?= $error['message'] ?></td>
        <td><?= $error['count'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
