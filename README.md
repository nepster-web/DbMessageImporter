# DbMessageImporter
DbMessageImporter Комнонент принимает массив данных с переводами и импортирует в базу данных.

Если Вы используете yii\i18n\DbMessageSource в своем проекте, то с помощью данного компонента
можно без труда переносить и обновлять данные переводов (например из файлов) в специальные таблицы.


Установка
---------

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require nepster-web/yii2-db-message-importer: dev-master
```

или добавьте

```
"nepster-web/yii2-db-message-importer": "dev-master"
```

в файл `composer.json` в секцию require.


Использование
-------------

Пример валидного массива с данными:

  ~~~
     Array
     (
        [users.main] => Array
            (
                [SIGNUP] => Array
                    (
                        [ru] => Регистрация
                        [en] => Signup
                    )

                [SIGNIN] => Array
                    (
                        [ru] => Вход
                        [en] => Login
                    )
            )
     )
  ~~~


  Пример обновления базы данных переводов:

  file.yml
  ~~~
  # Категория users.main
  "users.main":
    "SIGNUP":
      ru: 'Регистрация'
      en: 'Signup'
    "SIGNIN":
      ru: 'Вход'
      en: 'Login'
  ~~~

  run
  ~~~
  use nepster\yii2components\DbMessageImporter;
  ...
  $yaml = Yaml::parse(file_get_contents('/path/to/file.yml'));
  $DbMessageImporter = new DbMessageImporter($yaml);
  $DbMessageImporter->setMessageTable('{{%language_messages}}');
  $DbMessageImporter->setSourceMessageTable('{{%language_source_messages}}');
  $DbMessageImporter->update(); // return true or false
  ~~~




  На практике
  -----------
  
  Можно реализовать консольную команду, которая используя данный компонент будет обновлять базу данных с переводами.

  ~~~
  <?php

  namespace console\controllers;

  use nepster\yii2components\DbMessageImporter;
  use Symfony\Component\Yaml\Yaml;
  use yii\helpers\Console;
  use yii\log\Logger;
  use Yii;

  /**
   * DB Translater
   */
  class TranslateController extends \yii\console\Controller
  {
      /**
       * @var array Файлы переводов
       */
      private $transtaleYmlFiles = [
          '@frontend/modules/markets/translations/markets.yml'
      ];

      /**
       * Обновить базу данных переводов
       */
      public function actionUpdate()
      {
          foreach ($this->transtaleYmlFiles as &$file) {
              if (is_string($file)) {
                  $filePath = Yii::getAlias($file);
                  $content = @file_get_contents($filePath);
                  try {
                      $yaml = Yaml::parse($content);
                      $DbMessageImporter = new DbMessageImporter($yaml);
                      $DbMessageImporter->setMessageTable('{{%language_messages}}');
                      $DbMessageImporter->setSourceMessageTable('{{%language_source_messages}}');
                      $result = $DbMessageImporter->update();
                      if ($result) {
                          $this->stdout("SUCCESS" . PHP_EOL . $filePath . PHP_EOL . PHP_EOL, Console::FG_GREEN);
                      } else {
                          $this->stdout("FAIL: not update" . PHP_EOL . $filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                      }
                  } catch (\Exception $e) {
                      $this->stdout("FAIL: YML error" . PHP_EOL . $filePath . PHP_EOL . PHP_EOL, Console::FG_RED);
                  }
              }
          }
      }
  }
  ~~~