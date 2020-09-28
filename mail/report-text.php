<?= Yii::t('app/modules/guard', 'Total scan result from {datetime}', [
    'datetime' => date("F d Y H:i:s", $details['timestamp'])
]); ?>
<?= Yii::t('app/modules/guard', 'Scanning {dirs} dirs and {files} files completed in {time} sec.', [
    'dirs' => $details['dirs'],
    'files' => $details['files'],
    'time' => round($details['time'], 2)
]); ?>
<?php
    if (count($files) > 0) :
?>
<?= Yii::t('app/modules/guard', 'The following files have been added/changed:'); ?>
<?php
        foreach ($files as $file) {
            echo Yii::t('app/modules/guard', 'File `{filename}` has been modified at {modified}', [
                'filename' => $file['filename'],
                'modified' => $file['modified'],
            ]);
        }
    else :
?>
<?= Yii::t('app/modules/guard', 'There are no changes to tracked files.'); ?>
<?php
    endif;
?>