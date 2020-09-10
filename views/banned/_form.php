<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\Security */
/* @var $form yii\widgets\ActiveForm */

\yii\web\YiiAsset::register($this);

?>

<div class="banned-form">
    <?php
    $form = ActiveForm::begin([
        'id' => "formBanned",
        'enableAjaxValidation' => true,
        'options' => [
            'enctype' => 'multipart/form-data'
        ]
    ]);
    ?>

    <?= $form->field($model, 'ip')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'status')->widget(SelectInput::class, [
        'items' => $model->getStatuses(),
        'options' => [
            'id' => 'banned-form-status',
            'class' => 'form-control'
        ]
    ]) ?>

    <div class="modal-footer">
        <?= Html::a(Yii::t('app/modules/guard', 'Close'), "#", [
            'class' => 'btn btn-default pull-left',
            'data-dismiss' => 'modal'
        ]); ?>
        <?= Html::submitButton(Yii::t('app/modules/guard', 'Apply'), ['class' => 'btn btn-save btn-success pull-right']) ?>
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
    $("#formBanned").on("afterValidateAttribute", afterValidateAttribute);
});
JS
); ?>