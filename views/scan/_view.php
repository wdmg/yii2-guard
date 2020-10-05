<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\Scanning */

\yii\web\YiiAsset::register($this);

?>
<div class="scan-view-report">
    <?= GridView::widget([
        'filterModel' => $model,
        'dataProvider' => $report,
        'formatter' => ['class' => 'yii\i18n\Formatter', 'nullDisplay' => ''],
        'columns' => [
            [
                'attribute' => 'mimetype',
                'label' => Yii::t('app/modules/guard', 'Type'),
                'format' => 'raw',
                'contentOptions' => [
                    'style' => "word-break:break-all;"
                ],
                'value' => function($data) {
                    return ((isset($data['mimetype'])) ? $data['mimetype'] : null);
                }
            ], [
                'attribute' => 'filename',
                'label' => Yii::t('app/modules/guard', 'Name'),
                'format' => 'raw',
                'contentOptions' => [
                    'style' => "word-break:break-all;"
                ],
                'value' => function($data) {
                    return ((isset($data['filename'])) ? $data['filename'] : null);
                }
            ], [
                'attribute' => 'modified',
                'label' => Yii::t('app/modules/guard', 'Last modified'),
                'format' => 'raw',
                'value' => function($data) {
                    return ((isset($data['modified'])) ? $data['modified'] : null);
                }
            ], [
                'attribute' => 'filesize',
                'label' => Yii::t('app/modules/guard', 'Size'),
                'format' => 'raw',
                'value' => function($data) {
                    return ((isset($data['filesize'])) ? $data['filesize'] : null);
                }
            ], [
                'attribute' => 'filehash',
                'label' => Yii::t('app/modules/guard', 'Hash'),
                'format' => 'raw',
                'value' => function($data) {
                    return ((isset($data['filehash'])) ? $data['filehash'] : null);
                }
            ],

        ],
    ]) ?>
</div>