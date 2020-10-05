<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Progress;

/* @var $this yii\web\View */

\yii\web\YiiAsset::register($this);

?>
<div class="scan-scan-log">
    <?= Html::textArea('scanLog', "", [
        'id' => 'scanLog',
        'class' => 'form-control',
        'rows' => '10',
        'readonly' => true
    ]); ?>
    <div class="modal-footer">
        <?= Progress::widget([
            'percent' => 0,
            'barOptions' => [
                'class' => 'progress-bar-info'
            ],
            'options' => [
                'id' => 'scanProgress',
                'class' => 'active progress-striped',
                /*'style' => 'display: none;'*/
            ]
        ]); ?>
    </div>
</div>

<?php
$url = Url::to(['scan/scan']);
$this->registerJs(<<< JS

    $.get('$url' + '?action=run');
    var status;
    var timestamps = [];
    var progress = setInterval(function() {
        if (status !== 'complete' || status !== 'failed') {
            $.ajax({
                url: '$url' + '?action=progress',
                async : true,
                dataType: "JSON",
                success: function(response) {
                    if (response.progress) {
                        $('#scanProgress .progress-bar').css("width", parseInt(response.progress) + "%");
                    }
                    if (response.log) {
                        $.each(response.log, function(timestamp, message) {
                            console.log(timestamp);
                            console.log(timestamps.indexOf(timestamp));
                            if (timestamps.indexOf(timestamp) == -1) {
                                var scanLog = $("#scanLog");
                                scanLog.val(scanLog.val() + new Date(timestamp * 1000) + ' - ' + message);
                                timestamps.push(timestamp);
                            }
                        });
                        $('#scanLog').scrollTop($('#scanLog').get(0).scrollHeight);
                        console.log(timestamps);
                    }
                    if (response.status) {
                        status = response.status;
                        if (status == 'complete' || status == 'failed') {
                            clearInterval(progress);
                            $('#scanReport').modal('hide');
                            $.pjax.reload({container: '#guardScannedAjax'});
                        }
                    }
                }
            });
        } else if (status == 'complete' || status == 'failed') {
            clearInterval(progress);
        }
    }, 5000);
JS
); ?>