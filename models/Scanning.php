<?php

namespace wdmg\guard\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use wdmg\helpers\ArrayHelper;
use wdmg\helpers\FileHelper;

class Scanning extends \yii\db\ActiveRecord
{

    private $_module;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if (!$this->_module = Yii::$app->getModule('admin/guard'))
            $this->_module = Yii::$app->getModule('guard');
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%guard_scanned}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => function () {
                    return date("Y-m-d H:i:s");
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['data', 'logs'], 'required'],
            [['data', 'logs'], 'string'],
            [['created_at', 'updated_at', 'release_at'], 'safe'],
        ];

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app/modules/guard', 'ID'),
            'data' => Yii::t('app/modules/guard', 'Data'),
            'logs' => Yii::t('app/modules/guard', 'Logs'),
            'created_at' => Yii::t('app/modules/guard', 'Created'),
            'updated_at' => Yii::t('app/modules/guard', 'Updated'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        if (is_array($this->data))
            $this->data = serialize($this->data);

        if (is_array($this->logs))
            $this->logs = serialize($this->logs);

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function afterFind()
    {
        parent::afterFind();

        if (is_string($this->data))
            $this->data = unserialize($this->data);

        if (is_string($this->logs))
            $this->logs = unserialize($this->logs);
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert && $this->_module->fileSystemScan["autoClear"]) {
            $this->clearOld();
        }
    }

    /**
     * @return array|null
     */
    public function scan()
    {
        // Get more time for process
        set_time_limit(0);

        // Get file only types
        $onlyTypes = $this->_module->fileSystemScan["onlyTypes"];
        if (!$onlyTypes)
            $onlyTypes = ['*.php'];

        // Get file except types
        $exceptTypes = $this->_module->fileSystemScan["exceptTypes"];
        if (!$exceptTypes)
            $exceptTypes = [];

        // Get excludes paths
        $excludes = $this->_module->fileSystemScan["excludesPath"];
        if (!$excludes)
            $excludes = [
                '@runtime',
                '@tests',
                '@runtime/cache',
                '@webroot/assets',
            ];

        $runtime = [];
        $time_start = microtime(true);
        $runtime['log'][time()] = Yii::t('app/modules/guard', 'Start scanning...');

        // Точка входа
        $path = Yii::getAlias('@app');
        $directories = FileHelper::findDirectories($path, ['recursive' => true]);

        // Список игнорирования при сканировании
        $ignored = [];
        foreach ($excludes as $exclude) {
            if (preg_match("/^@(.*)$/", $exclude)) {
                try {
                    $ignored[] = Yii::getAlias($exclude);
                } catch (\yii\base\InvalidArgumentException $e) {
                    $runtime['log'][time()] = Yii::t('app/modules/guard', 'Exclude alias not found: {alias}', [
                        'alias' => $exclude
                    ]);
                }
            } elseif (is_dir($exclude)) {
                $ignored[] = $exclude;
            } else {
                $runtime['log'][time()] = Yii::t('app/modules/guard', 'Exclude path is not a directory: {path}', [
                    'path' => $exclude
                ]);
            }
        }

        // Сканирование файлов и директорий
        $scanned = [];
        $dirs_count = 0;
        $files_count = 0;
        foreach ($directories as $key => $directory) {
            if (in_array($directory, $ignored)) {
                $runtime['log'][time()] = Yii::t('app/modules/guard', 'Excluded path will be ignored: {path}', [
                    'path' => $directory
                ]);
                unset($directories[$key]);
            } else {
                $files = FileHelper::findFiles($directory, [
                    'only' => $onlyTypes,
                    'except' => $exceptTypes,
                    'recursive' => false
                ]);

                if (!empty($files)) {
                    foreach ($files as $file) {
                        $path = FileHelper::normalizePath($file);
                        $lastmod = \filemtime($path);
                        $hash = \md5_file($path);
                        $size = \filesize($path);

                        $scanned[$directory][$file][] = [
                            'lastmod' => $lastmod,
                            'hash' => $hash,
                            'size' => $size,
                        ];

                        $runtime['log'][time()] = Yii::t('app/modules/guard', 'Scanned `{file}`, md5 hash: {hash}, last modification time of the file: {lastmod}', [
                            'file' => $file,
                            'hash' => $hash,
                            'size' => $size,
                            'lastmod' => date("F d Y H:i:s", $lastmod)
                        ]);

                        $files_count++;

                    }
                }
                $dirs_count++;
            }
        }

        // Сканирование завершено
        if (!empty($scanned)) {
            $time = (microtime(true) - $time_start);

            $runtime['summary'] = [
                'dirs' => $dirs_count,
                'files' => $files_count,
                'time' => $time
            ];

            $runtime['log'][time()] = Yii::t('app/modules/guard', 'Scanning {dirs} dirs and {files} files completed in {time} sec.', [
                'dirs' => $dirs_count,
                'files' => $files_count,
                'time' => round($time, 2)
            ]);

            // Проверка на модификации и отправка отчёта
            if ($differences = $this->compare($scanned)) {
                $runtime['log'][time()] = Yii::t('app/modules/guard', 'Changes detected. {count} files have been modified since the last scan.', [
                    'count' => count($differences),
                ]);
                $this->sendReport($differences);
            }

            // Сохранение текущего сканирования
            if (!empty($scanned) && !empty($runtime)) {
                $this->data = $scanned;
                $this->logs = $runtime;

                if ($this->save(true)) {
                    // Activity
                } else {
                    // Activity
                }
            }

            return $runtime;
        }

        return null;
    }

    /**
     * @param $data
     * @return array|null
     */
    private function compare($data)
    {
        if ($lastscan = self::find()->orderBy(['id' => SORT_DESC])->one()) {
            if ($results = ArrayHelper::diff($data, ((is_array($lastscan->data)) ? $lastscan->data : unserialize($lastscan->data)))) {
                return $results;
            }
        }

        return null;
    }

    /**
     * @param $data
     * @return array
     */
    private function buildReport($data) {
        $report = [];

        if (!$root = Yii::getAlias('@app'))
            $root = dir(__DIR__);

        foreach ($data as $paths) {
            foreach ($paths as $file => $details) {
                $report[] = [
                    'filename' => FileHelper::safetyPath($file, $root),
                    'modified' => date("F d Y H:i:s", $details[0]['lastmod']),
                ];
            }
        }
        return $report;
    }

    /**
     * @param $data
     * @return bool
     */
    private function sendReport($data)
    {
        // Get report email adress
        $reportEmail = $this->_module->scanReport["reportEmail"];
        if (!$reportEmail)
            $reportEmail = Yii::$app->params['supportEmail'];

        if ($mailer = Yii::$app->getMailer()) {
            return $mailer->compose([
                'html' => $this->_module->scanReport["emailViewPath"]["html"],
                'text' => $this->_module->scanReport["emailViewPath"]["text"]
            ], [
                'files' => $this->buildReport($data)
            ])->setTo($reportEmail)
                ->setSubject(Yii::t('app/modules/guard', 'Scan report for {appname}', [
                    'appname' => Yii::$app->name,
                ]))
                ->send();
        } else {
            // Activity
        }
        return false;
    }

    /**
     * @return int
     */
    private function clearOld()
    {
        return self::updateAll(
            ['data' => null],
            ['AND', ['<=', 'id', intval($this->id) - 3], ['!=', 'id', $this->id]]
        );
    }
}