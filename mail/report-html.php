<?php

foreach ($files as $file) {
    echo Yii::t('app/modules/guard', 'File `{filename}` has been modified at {modified}', [
        'filename' => $file['filename'],
        'modified' => $file['modified'],
    ]);
}