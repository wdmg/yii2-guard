<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\BannedForm */
/* @var $form yii\widgets\ActiveForm */

\yii\web\YiiAsset::register($this);

?>

<div class="banned-form-test">
    <?php
    $form = ActiveForm::begin([
        'id' => "formTest",
        'action' => ['banned/test'],
        'enableAjaxValidation' => true,
        'options' => [
            'enctype' => 'multipart/form-data'
        ]
    ]);
    ?>

    <?= $form->field($model, 'ip')->textarea(['rows' => 6]) ?>

    <div class="alert alert-info">
        <p><?= Yii::t('app/modules/guard', 'Specify a list of IP addresses (each address - on a new line). The following options are allowed:') ?></p>
        <ul>
            <li><?= Yii::t('app/modules/guard', 'IPv4 address (for example: 172.104.89.12)') ?></li>
            <?php /* <li><?= Yii::t('app/modules/guard', 'network address in the CIDR (for example: 172.104.89.12/24)') ?></li> */ ?>
            <?php /* <li><?= Yii::t('app/modules/guard', 'network address with mask 172.104.89.0/255.255.255.0') ?></li> */ ?>
            <?php /* <li><?= Yii::t('app/modules/guard', 'address range like 172.104.89.0-172.104.89.255') ?></li> */ ?>
            <?php /* <li><?= Yii::t('app/modules/guard', 'IPv6 address or network (2002::ac68:590c, 2002::ac68:5900/120)') ?></li> */ ?>
        </ul>
    </div>

    <div class="modal-footer">
        <?= Html::a(Yii::t('app/modules/guard', 'Close'), "#", [
            'class' => 'btn btn-default pull-left',
            'data-dismiss' => 'modal'
        ]); ?>
        <?= Html::submitButton(Yii::t('app/modules/guard', 'Go'), [
            'class' => 'btn btn-refresh btn-success pull-right',
            'name' => 'process',
            'value' => '1',
        ]) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
<?php $this->registerJs(<<< JS
$(document).ready(function() {
    function afterValidateAttribute(event, attribute, messages)
    {
        var form = $(event.target);
        $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serializeArray(),
            }
        ).done(function(data) {
            form.yiiActiveForm('validateAttribute', 'options-type');
        }).fail(function () {
            
        });
        return false; // prevent default form submission
    }
    $("#formTest").on("afterValidateAttribute", afterValidateAttribute);
    $("#formTest").on("submit", function(event) {
        event.preventDefault();
        var form = $(event.target).clone();
        
        $("<input />").attr("type", "hidden").attr("name", "process").attr("value", 1).appendTo(form);

        console.log(form.serializeArray());
        $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serializeArray(),
            }
        ).done(function(data) {
            $('#testIpNetwork .modal-body').html($(data).remove('.modal-footer'));
            if ($(data).find('.modal-footer').length > 0) {
                $('#testIpNetwork').find('.modal-footer').remove();
                $('#testIpNetwork .modal-content').append($(data).find('.modal-footer'));
            }
            $('#testIpNetwork').modal();            
        }).fail(function () {
            
        });
    });
});
JS
); ?>