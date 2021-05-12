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
        'id' => "guardScannedAjax",
        'timeout' => 5000
    ]); ?>
    <?= GridView::widget([
        'id' => "guardScannedList",
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
                    if (isset($data->logs['summary'])) {
                        return Yii::t('app/modules/guard', 'Scanning from {datetime}: {dirs} dirs and {files} files completed in {time} sec.', [
                            'datetime' => date("F d Y H:i:s", $data->logs['summary']['timestamp']),
                            'dirs' => $data->logs['summary']['dirs'],
                            'files' => $data->logs['summary']['files'],
                            'time' => round(intval($data->logs['summary']['time']), 2),
                        ]);
                    } else {
                        return null;
                    }
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
                                'data' => [
                                    'toggle' => 'modal',
                                    'target' => '#viewReport',
                                ]
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
            'prevPageCssClass' => 'prev',
            'nextPageCssClass' => 'next',
            'firstPageCssClass' => 'first',
            'lastPageCssClass' => 'last',
            'firstPageLabel' => Yii::t('app/modules/guard', 'First page'),
            'lastPageLabel'  => Yii::t('app/modules/guard', 'Last page'),
            'prevPageLabel'  => Yii::t('app/modules/guard', '&larr; Prev page'),
            'nextPageLabel'  => Yii::t('app/modules/guard', 'Next page &rarr;')
        ],
    ]); ?>
    <hr/>
    <div>
        <div class="btn-group">
            <?= Html::a(Yii::t('app/modules/guard', 'Clear Up'), ['scan/clear'], [
                'class' => 'btn btn-warning'
            ]) ?>
            <?= Html::a(Yii::t('app/modules/guard', 'Run Scan'), ['scan/scan'], [
                'class' => 'btn btn-success',
                'data' => [
                    'toggle' => 'modal',
                    'target' => '#scanReport',
                ]
            ]) ?>
        </div>
        <?= Html::a(Yii::t('app/modules/guard', 'Delete all reports'), ['scan/delete'], [
            'class' => 'btn btn-delete btn-danger pull-right',
            'data' => [
                'method' => 'post',
            ]
        ]) ?>
    </div>
    <?php Pjax::end(); ?>
</div>

<?php
$this->registerJs(<<< JS
    $('body').delegate('[data-toggle="modal"][data-target]', 'click', function(event) {
        event.preventDefault();
        var target = $(event.target).data('target');
        $.get(
            $(this).attr('href'),
            function (data) {
                $(target).find('.modal-body').html($(data).remove('.modal-footer'));
                if ($(data).find('.modal-footer').length > 0) {
                    $(target).find('.modal-footer').remove();
                    $(target).find('.modal-content').append($(data).find('.modal-footer'));
                }
                
                if ($(target).find('button[type="submit"]').length > 0 && $(target).find('form').length > 0) {
                    $(target).find('button[type="submit"]').on('click', function(event) {
                        event.preventDefault();
                        $(target).find('form').submit();
                    });
                }
                
                $(target).modal();
            }  
        );
    });
JS
); ?>

<?php Modal::begin([
    'id' => 'viewReport',
    'header' => '<h4 class="modal-title">'.Yii::t('app/modules/guard', 'View Report').'</h4>',
    'footer' => '<a href="#" class="btn btn-primary" data-dismiss="modal">'.Yii::t('app/modules/guard', 'Close').'</a>',
    'clientOptions' => [
        'show' => false
    ]
]); ?>
<?php Modal::end(); ?>

<?php Modal::begin([
    'id' => 'scanReport',
    'header' => '<h4 class="modal-title">'.Yii::t('app/modules/guard', 'Scan Report').'</h4>',
    'footer' => '<a href="#" class="btn btn-primary" data-dismiss="modal">'.Yii::t('app/modules/guard', 'Close').'</a>',
    'clientOptions' => [
        'show' => false
    ]
]); ?>
<?php Modal::end(); ?>

<?php echo $this->render('../_debug'); ?>