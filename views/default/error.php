<?php

use yii\helpers\Html;
use yii\web\View;

?>
<div class="page-header">
    <h1>
        <?php
            if (isset($status)) {
                $this->title = $code ." " . $status;
            } else {
                $this->title = $code;
            }
        ?>
        <?= $this->title ?>
    </h1>
</div>
<div class="error">
    <div class="alert alert-danger">
        <?= Html::encode($message); ?>
    </div>
</div>
