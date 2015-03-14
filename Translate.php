<?php

namespace nepster\messagesimporter;

use yii\base\InvalidParamException;
use Symfony\Component\Yaml\Yaml;
use yii\helpers\Console;
use Yii;

/**
 * Translations importer
 */
class Translate extends \yii\console\Controller
{
    /**
     * @var array Файлы переводов в формате Yml
     */
    public $YmlFiles = [];

    /**
     * @var string Тип импорта
     */
    public $config = [
        'file' => [
            'translatePath' => '@app/test'
        ],
        'db' => [
            'messageTable' => '{{%language_messages}}',
            'sourceMessageTable' => '{{%language_source_messages}}',
            'connection' => 'db',
        ]
    ];

    /**
     * @var string Тип импорта
     */
    public $type = 'db';

    /**
     * @var bool
     */
    public $update;

    /**
     * @var object
     */
    private $_importer;

    /**
     * @inheritdoc
     */
    public function options()
    {
        return ['type', 'update'];
    }

    /**
     * Import
     */
    public function actionIndex()
    {
        try {

            // Получаем необходимые данные
            switch ($this->type) {

                case 'db':
                    assert(isset($this->config['db']['messageTable']));
                    assert(isset($this->config['db']['sourceMessageTable']));

                    $this->_importer = new DbMessageImporter();
                    $this->_importer->setMessageTable($this->config['db']['messageTable']);
                    $this->_importer->setSourceMessageTable($this->config['db']['sourceMessageTable']);
                    $this->_importer->setConnection(isset($this->config['db']['connection']) ? $this->config['db']['connection'] : 'db');
                    $this->_importer->setUpdate($this->update);
                    break;

                case 'file':
                    assert(isset($this->config['file']['translatePath']));
                    $translatePath = $this->prompt('Enter path to messages [' . $this->config['file']['translatePath'] . ']:');
                    if (!$translatePath) {
                        $translatePath = $this->config['file']['translatePath'];
                    }
                    $this->_importer = new FileMessageImporter($translatePath);
                    break;

                default:
                    throw new InvalidParamException('Type ' . $this->type . ' is not supported');
            }

            // Импорт данных переводов
            foreach ($this->YmlFiles as $file) {
                $filePath = Yii::getAlias($file);
                $content = @file_get_contents($filePath);
                $translateArray = Yaml::parse($content);

                $import = false;
                if (is_array($translateArray)) {
                    $import = $this->_importer->import($translateArray);
                }

                if (is_array($import)) {
                    foreach ($import as $_filePath => $result) {
                        if ($result === true) {
                            $this->stdout("SUCCESS" . PHP_EOL . $_filePath . PHP_EOL . PHP_EOL, Console::FG_GREEN);
                        } else {
                            $this->stdout("FAIL" . PHP_EOL . $_filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                        }
                    }
                } else {
                    if ($import === true) {
                        $this->stdout("SUCCESS" . PHP_EOL . $filePath . PHP_EOL . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("FAIL" . PHP_EOL . $filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                    }
                }
            }

        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . PHP_EOL, Console::FG_RED);
        }
    }
}