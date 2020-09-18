<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\BannedForm */
/* @var $form yii\widgets\ActiveForm */

\yii\web\YiiAsset::register($this);

?>
<div class="banned-form-results">
    <?= DetailView::widget([
        'model' => $results,
        'attributes' => array_map(function ($key) use ($model) {
            return [
                'label' => $key,
                'attribute' => $key,
                'format' => 'raw',
                'value' => function($data) use ($key, $model) {
                    if ($data[$key] == $model::GUARD_STATUS_IS_RELEASED)
                        return '<span class="label label-success">'.Yii::t('app/modules/guard','Released').'</span>';
                    elseif ($data[$key] == $model::GUARD_STATUS_IS_UNBANNED)
                        return '<span class="label label-info">'.Yii::t('app/modules/guard','Unbanned').'</span>';
                    elseif ($data[$key] == $model::GUARD_STATUS_IS_BANNED)
                        return '<span class="label label-danger">'.Yii::t('app/modules/guard','Banned').'</span>';
                    else
                        return '<span class="label label-default">'.Yii::t('app/modules/guard','Not banned').'</span>';
                }
            ];
        }, array_keys($results))
    ]) ?>
    <div class="modal-footer">
        <?= Html::a(Yii::t('app/modules/guard', 'Close'), "#", [
            'class' => 'btn btn-default pull-right',
            'data-dismiss' => 'modal'
        ]); ?>
    </div>
</div>