<?php

namespace nepster\messagesimporter;

use yii\base\InvalidParamException;
use yii\db\Connection;
use yii\db\Query;
use Yii;

/**
 * DbMessageImporter импортирует данные переводов в базу данных
 */
class DbMessageImporter
{
    /**
     * @var string Таблица с переводами
     */
    private $_messageTable;

    /**
     * @var string Таблица с константами для перевода
     */
    private $_sourceMessageTable;

    /**
     * @var \yii\db\Connection
     */
    private $_db;

    /**
     * @var bool
     */
    private $_update;

    /**
     * Init
     */
    public function __construct()
    {
        $this->_db = Yii::$app->db;
    }

    /**
     * Устанавливает таблицу с переводами
     * @param string $table
     */
    public function setMessageTable($table)
    {
        $this->_messageTable = $table;
    }

    /**
     * Устанавливает таблицу с константами для перевода
     * @param string $table
     */
    public function setSourceMessageTable($table)
    {
        $this->_sourceMessageTable = $table;
    }

    /**
     * Соединение с базой данных
     */
    public function setConnection($connection)
    {
        if (Yii::$app->$connection instanceof Connection) {
            $this->_db = Yii::$app->$connection;
        }
    }

    /**
     * Устанавливает флаг, при котором переводы будут обновляться
     * @param bool $update
     */
    public function setUpdate($update)
    {
        $this->_update = $update;
    }

    /**
     * Обновление базы данных
     */
    public function import(array $translateArray)
    {
        $query = new Query;
        $transaction = $this->_db->beginTransaction();
        try {
            // Обходим каждую категорию
            foreach ($translateArray as $category => &$messages) {
                // обходим каждую константу
                foreach ($messages as $constant => &$message) {
                    // Достаем константу с переводов в указанной категории
                    $selectCategory = $query->from($this->_sourceMessageTable)
                        ->where(['category' => $category, 'message' => $constant])
                        ->createCommand($this->_db)
                        ->queryOne();
                    // Если такая константа уже существует, необходимо обновить перевод
                    if ($selectCategory) {
                        $constantId = $selectCategory['id'];
                    } else { // Если такой константы нет, создадим ее
                        $insert = $this->_db->createCommand()->insert($this->_sourceMessageTable, [
                            'category' => $category,
                            'message' => $constant,
                        ])->execute();
                        $constantId = $this->_db->lastInsertID;
                    }
                    // обходим каждый перевод сообщения
                    foreach ($message as $lang => &$translate) {
                        // Достаем перевод исходя из константы и языка
                        $selectTranslate = $query->from($this->_messageTable)
                            ->where(['id' => $constantId, 'language' => $lang])
                            ->createCommand($this->_db)
                            ->queryOne();
                        // Если такой перевод есть, небходимо его обновить
                        if ($selectTranslate) {
                            // Обновляем только в том случае, если действительно есть изменения
                            if ($selectTranslate['translation'] !== $translate && $this->_update) {
                                $update = $this->_db->createCommand()
                                    ->update($this->_messageTable, ['translation' => $translate], ['id' => $constantId, 'language' => $lang])
                                    ->execute();
                            }
                        } else { // Если перевода нет, вносим его
                            $insert = $this->_db->createCommand()->insert($this->_messageTable, [
                                'id' => $constantId,
                                'language' => $lang,
                                'translation' => $translate,
                            ])->execute();
                        }
                    }
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new InvalidParamException($e->getMessage());
        }
    }

}
