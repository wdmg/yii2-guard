<?php

namespace wdmg\guard\models;

use Yii;
use wdmg\base\models\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use wdmg\helpers\ArrayHelper;
use wdmg\helpers\FileHelper;
use wdmg\validators\SerialValidator;

class Scanning extends ActiveRecord
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

        $validator = new SerialValidator();
        if (is_string($this->data) && $validator->isValid($this->data))
            $this->data = unserialize($this->data);
        else
            $this->data = [];

        if (is_string($this->logs) && $validator->isValid($this->logs))
            $this->logs = unserialize($this->logs);
        else
            $this->logs = [];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert && $this->_module->fileSystemScan["autoClear"]) {
            $this->clearOldReports();
        }
    }

    /**
     * Scans the file system for file modifications
     *
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
        $runtime['log'][time()] = Yii::t('app/modules/guard', 'Scanning started...');

        if ($cache = Yii::$app->getCache()) {
            $cache->delete('scan-report');
            $cache->set('scan-report', [
                'progress' => 1,
                'runtime' => $runtime,
                'status' => '',
            ], 3600);
        }


        // Point of entry
        $path = Yii::getAlias('@app');
        $directories = FileHelper::findDirectories($path, ['recursive' => true]);

        // Ignore list when scanning
        $ignored = [];
        foreach ($excludes as $exclude) {
            if (preg_match("/^@(.*)$/", $exclude)) {
                try {
                    $ignored[] = Yii::getAlias($exclude);
                } catch (\yii\base\InvalidArgumentException $e) {
                    $runtime['log'][time()] = Yii::t('app/modules/guard', 'Excluded alias `{alias}` not found', [
                        'alias' => $exclude
                    ]);
                }
            } elseif (is_dir($exclude)) {
                $ignored[] = $exclude;
            } else {
                $runtime['log'][time()] = Yii::t('app/modules/guard', 'Excluded path `{path}` is not a directory', [
                    'path' => $exclude
                ]);
            }
        }

        $isConsole = $this->_module->isConsole();
        if (!$isConsole) {
            sleep(2);
        }

        // Counting of files
        //$all_files = [];
        $all_files_count = 0;
        foreach ($directories as $key => $directory) {
            if (!in_array($directory, $ignored)) {
                $files = FileHelper::findFiles($directory, [
                    'only' => $onlyTypes,
                    'except' => $exceptTypes,
                    'recursive' => false
                ]);
                //$all_files = array_merge($all_files, $files);
                $all_files_count = count($files) + $all_files_count;
            }
        }

        // Scan files and directories
        $scanned = [];
        $dirs_count = 0;
        $files_count = 0;
        foreach ($directories as $key => $directory) {
            if (in_array($directory, $ignored)) {
                $runtime['log'][time()] = Yii::t('app/modules/guard', 'Excluded path `{path}` will be ignored', [
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
                        $hash = \mb_substr(\md5_file($path), 0, 12);
                        $size = \filesize($path);

                        $scanned[$directory][$file][] = [
                            'lastmod' => $lastmod,
                            'hash' => $hash,
                            'size' => $size
                        ];

                        $runtime['log'][time()] = Yii::t('app/modules/guard', 'Scanned `{file}`, md5 hash: {hash}, last modification time of the file: {lastmod}', [
                            'file' => $file,
                            'hash' => $hash,
                            'size' => $size,
                            'lastmod' => date("F d Y H:i:s", $lastmod)
                        ]);

                        $files_count++;

                        $cache->set('scan-report', [
                            'progress' => (($files_count/$all_files_count)*100),
                            'runtime' => $runtime,
                            'status' => 'process',
                        ], 3600);

                        if (!$isConsole) {
                            sleep(2);
                        }
                    }
                }
                $dirs_count++;
            }
        }

        // Scan completed
        if (!empty($scanned)) {
            $time = (microtime(true) - $time_start);

            $runtime['summary'] = [
                'dirs' => $dirs_count,
                'files' => $files_count,
                'time' => $time,
                'timestamp' => time(),
                'modified' => null
            ];

            $runtime['log'][time()] = Yii::t('app/modules/guard', 'Scanning {dirs} dirs and {files} files completed in {time} sec.', [
                'dirs' => $dirs_count,
                'files' => $files_count,
                'time' => round($time, 2)
            ]);

            // Check for modifications and send a report
            if ($differences = $this->compareReports($scanned)) {
                if ($count = $this->countDifferences($differences)) {

                    $runtime['log'][time()] = Yii::t('app/modules/guard', 'Changes detected! {count} files have been modified since the last scan.', [
                        'count' => $count,
                    ]);

                    $runtime['summary']['modified'] = $count;

                    $this->_module->logActivity(
                        "Changes detected! $count files have been modified since the last scan.",
                        __METHOD__,
                        'danger',
                        1
                    );

                    $this->sendReport($differences, $runtime['summary']);
                } else {
                    $this->sendReport([], $runtime['summary']);
                }
            }

            // Save the current scan
            if (!empty($scanned) && !empty($runtime)) {
                $this->data = $scanned;
                $this->logs = $runtime;
                if ($this->save(true)) {
                    $this->_module->logActivity(
                        'Scan report has been saved successfully.',
                        __METHOD__,
                        'success',
                        1
                    );
                } else {
                    $this->_module->logActivity(
                        'An error occurred while save the scan report.',
                        __METHOD__,
                        'warning',
                        1
                    );
                }
            }


            $cache->set('scan-report', [
                'progress' => 100,
                'runtime' => $runtime,
                'status' => 'complete',
            ], 3600);

            return $runtime;
        }

        return null;
    }

    /**
     * Compares the scan result with the previous scan
     *
     * @param $data, current scan data
     * @param null $id, target report `id`
     * @return array|null
     */
    public function compareReports($data, $id = null)
    {
        if ($id)
            $lastscan = self::find()->where(['id' => intval($id)])->limit(1)->one()->getPrev();
        else
            $lastscan = self::find()->orderBy(['id' => SORT_DESC])->limit(1)->one();

        if ($lastscan) {
            if ($results = ArrayHelper::diff($data, ((is_array($lastscan->data)) ? $lastscan->data : unserialize($lastscan->data)))) {
                return $results;
            }
        }

        return null;
    }

    /**
     * Сhecks if reports can be compared/available.
     * Reports data are periodically deleted by age and may be unavailable.
     *
     * @param $id
     * @param null $last_id
     * @return bool
     */
    public function canCompareReports($id, $last_id = null) {

        if (!$last_id)
            $last_id = $this->getPrev(false, 1);

        $query = self::find()
            ->where(['id' => intval($id)])
            ->andWhere(['NOT', ['data' => null]])
            ->orWhere(['id' => intval($last_id)])
            ->andWhere(['NOT', ['data' => null]]);

        $count = $query->count();
        return (intval($count) == 2) ? true : false;
    }

    /**
     * Builds a list of files that have been added or modified
     *
     * @param $data
     * @param bool $safe
     * @return array
     */
    public function buildReport($data, $safe = true) {
        $report = [];

        if (!$root = Yii::getAlias('@app'))
            $root = dir(__DIR__);

        if (is_array($data)) {
            foreach ($data as $paths) {
                foreach ($paths as $file => $details) {
                    $report[] = [
                        'filename' => (($safe) ? FileHelper::safetyPath($file, $root) : $file),
                        'filesize' => ((isset($details[0]['size'])) ? $details[0]['size'] : null),
                        'filehash' => ((isset($details[0]['hash'])) ? $details[0]['hash'] : null),
                        'mimetype' => FileHelper::getMimeTypeByExtension($file),
                        'modified' => ((isset($details[0]['lastmod'])) ? date("F d Y H:i:s", $details[0]['lastmod']) : null),
                    ];
                }
            }
        }

        return $report;
    }

    /**
     * Counting of elements in differences array
     *
     * @param $data
     * @return int
     */
    private function countDifferences($data) {
        $count = 0;
        if (is_array($data)) {
            foreach ($data as $paths) {
                foreach ($paths as $file) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Sends a scan report to the email address
     *
     * @param $data
     * @return bool
     */
    private function sendReport($data, $runtime)
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
                'files' => $this->buildReport($data),
                'details' => $runtime
            ])->setTo($reportEmail)->setSubject(Yii::t('app/modules/guard', 'Scan report for {appname}', [
                'appname' => Yii::$app->name,
            ]))->send();
        } else {
            $this->_module->logActivity(
                'An error occurred while send the scan report.',
                __METHOD__,
                'warning',
                1
            );
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function deleteAll($condition = null, $params = [])
    {
        $condition = array_merge((is_array($condition) ? $condition : []), ['NOT', ['id' => 'MAX(`id`)']]);
        return parent::deleteAll($condition, $params);
    }

    /**
     * Cleans up data from previous crawls to save database resources
     *
     * @return int
     */
    public function clearOldReports($allReports = false)
    {
        if ($allReports) {
            return self::updateAll(
                ['data' => null],
                ['AND', ['!=', 'id', $this->id]]
            );
        } else {
            return self::updateAll(
                ['data' => null],
                ['AND', ['<', 'updated_at', date("Y-m-d H:i:s", strtotime('-2 days'))], ['!=', 'id', $this->id]]
            );
        }
    }
}