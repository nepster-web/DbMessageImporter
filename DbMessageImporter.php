<?php

namespace nepster\yii2components;

use Yii;
use yii\db\Query;
use yii\base\InvalidParamException;
use yii\db\Connection;


/**
 * DbMessageImporter Комнонент принимает массив данных с переводами и импортирует в базу данных.
 *
 * Если Вы используете yii\i18n\DbMessageSource в своем проекте, то с помощью данного компонента
 * можно без труда переносить и обновлять данные переводов (например из файлов) в специальные таблицы.
 *
 * Пример валидного массива с данными:
 *
 * ~~~
 *  Array
 *   (
 *       [users.main] => Array
 *           (
 *               [SIGNUP] => Array
 *                   (
 *                       [ru] => Регистрация
 *                       [en] => Signup
 *                   )
 *
 *               [SIGNIN] => Array
 *                   (
 *                       [ru] => Вход
 *                       [en] => Login
 *                   )
 *           )
 *   )
 * ~~~
 *
 * Пример обновления базы данных переводов:
 *
 * file.yml
 * ~~~
 * # Категория users.main
 * "users.main":
 *   "SIGNUP":
 *     ru: 'Регистрация'
 *     en: 'Signup'
 *   "SIGNIN":
 *     ru: 'Вход'
 *     en: 'Login'
 * ~~~
 *
 * run
 * ~~~
 * use nepster\yii2components\DbMessageImporter;
 * ...
 * $yaml = Yaml::parse(file_get_contents('/path/to/file.yml'));
 * $DbMessageImporter = new DbMessageImporter($yaml);
 * $DbMessageImporter->setMessageTable('{{%language_messages}}');
 * $DbMessageImporter->setSourceMessageTable('{{%language_source_messages}}');
 * $DbMessageImporter->update(); // return true or false
 * ~~~
 */
class DbMessageImporter
{
    /**
     * @var string Таблица с переводами
     */
    private $_messageTable = '{{%messages}}';

    /**
     * @var string Таблица с константами для перевода
     */
    private $_sourceMessageTable = '{{%source_messages}}';

    /**
     * @var array Массив данных
     */
    private $_data;

    /**
     * @var yii\db\Connection
     */
    private $_db;

    /**
     * @var \Exception
     */
    private $_errors;

    /**
     * Конструктор принимает массив данных
     * Проверяет валидность формата
     */
    public function __construct(array $data)
    {
        foreach ($data as &$messages) {
            if (!is_array($messages)) {
                throw new InvalidParamException('Incorrect array: $messages must be array');
            }
            foreach ($messages as &$message) {
                if (!is_array($message)) {
                    throw new InvalidParamException('Incorrect array: $message must be array');
                }
            }
        }
        $this->init();
        $this->_data = $data;
    }

    /**
     * Инициализация
     */
    public function init()
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
    public function setConnection(Connection $connection)
    {
        $this->_db = $connection;
    }

    /**
     * Возвращает ошибки при откате транзакции
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Обновление базы данных
     */
    public function update()
    {
        $query = new Query;
        $transaction = $this->_db->beginTransaction();
        try {
            // Обходим каждую категорию
            foreach ($this->_data as $category => &$messages) {
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
                            if ($selectTranslate['translation'] !== $translate) {
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
            $this->_errors = $e;
            return false;
        }
    }

}
