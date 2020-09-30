<?php

use wdmg\widgets\DatePicker;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use wdmg\widgets\SelectInput;

/* @var $this yii\web\View */
/* @var $model wdmg\guard\models\Scanning */

$this->title = Yii::t('app/modules/guard', 'Scan Reports');
$this->params['breadcrumbs'][] = Yii::t('app/modules/guard', 'Security');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="page-header">
    <h1>
        <?= Html::encode($this->title) ?> <small class="text-muted pull-right">[v.<?= $this->context->module->version ?>]</small>
    </h1>
</div>
<div class="guard-banned-index">
    <?php Pjax::begin([
        'id' => "guardBannedAjax",
        'timeout' => 5000
    ]); ?>
    <?= GridView::widget([
        'id' => "guardBannedList",
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'layout' => '{summary}<br\/>{items}<br\/>{summary}<br\/><div class="text-center">{pager}</div>',
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'logs',
                'label' => Yii::t('app/modules/guard','Summary'),
                'format' => 'html',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    return Yii::t('app/modules/guard', 'Scanning from {datetime}: {dirs} dirs and {files} files completed in {time} sec.', [
                        'datetime' => date("F d Y H:i:s", $data->logs['summary']['timestamp']),
                        'dirs' => $data->logs['summary']['dirs'],
                        'files' => $data->logs['summary']['files'],
                        'time' => round(intval($data->logs['summary']['time']), 2),
                    ]);
                }
            ], [
                'attribute' => 'logs',
                'label' => Yii::t('app/modules/guard','Result'),
                'format' => 'html',
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'value' => function($data) {
                    if (isset($data->logs['summary']['modified'])) {
                        if ($count = $data->logs['summary']['modified']) {
                            return '<span class="label label-danger">' . Yii::t('app/modules/guard','{count} files modified', [
                                    'count' => $count
                                ]) . "</span>";
                        }
                    }
                    return '<span class="label label-success">' . Yii::t('app/modules/guard','No modified') . '</span>';
                }
            ],

            'created_at',
            'updated_at',

            [
                'class' => 'yii\grid\ActionColumn',
                'header' => Yii::t('app/modules/guard','Actions'),
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center'
                ],
                'template' => '{view}',
                'buttons' => [
                    'view' => function($url, $data) {
                        if (isset($data->logs['summary']['modified']) && $data->canCompareReports($data->id)) {
                            return Html::a(Yii::t('app/modules/guard','List of files'), $url, [
                                'data-toggle' => 'modal',
                                'data-target' => '#viewReport'
                            ]);
                        } else {
                            return '';
                        }
                    }
                ],
            ]
        ],
        'pager' => [
            'options' => [
                'class' => 'pagination',
            ],
            'maxButtonCount' => 5,
            'activePageCssClass' => 'active',
            'prevPageCssClass' => '',
            'nextPageCssClass' => '',
            'firstPageCssClass' => 'previous',
            'lastPageCssClass' => 'next',
            'firstPageLabel' => Yii::t('app/modules/guard', 'First page'),
            'lastPageLabel'  => Yii::t('app/modules/guard', 'Last page'),
            'prevPageLabel'  => Yii::t('app/modules/guard', '&larr; Prev page'),
            'nextPageLabel'  => Yii::t('app/modules/guard', 'Next page &rarr;')
        ],
    ]); ?>
    <?php Pjax::end(); ?>
</div>

<?php
$this->registerJs(<<< JS
    $('body').delegate('[data-toggle="modal"][data-target="#viewReport"]', 'click', function(event) {
        event.preventDefault();
        $.get(
            $(this).attr('href'),
            function (data) {
                $('#viewReport .modal-body').html($(data).remove('.modal-footer'));
                if ($(data).find('.modal-footer').length > 0) {
                    $('#viewReport').find('.modal-footer').remove();
                    $('#viewReport .modal-content').append($(data).find('.modal-footer'));
                }
                $('#viewReport').modal();
            }  
        );
    });
JS
); ?>

<?php Modal::begin([
    'id' => 'viewReport',
    'header' => '<h4 class="modal-title">'.Yii::t('app/modules/guard', 'Scan Report').'</h4>',
    'footer' => '<a href="#" class="btn btn-primary" data-dismiss="modal">'.Yii::t('app/modules/guard', 'Close').'</a>',
    'clientOptions' => [
        'show' => false
    ]
]); ?>
<?php Modal::end(); ?>

<?php echo $this->render('../_debug'); ?>