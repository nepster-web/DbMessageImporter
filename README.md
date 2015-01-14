# DbMessageImporter
DbMessageImporter Комнонент принимает массив данных с переводами и импортирует в базу данных.

Если Вы используете yii\i18n\DbMessageSource в своем проекте, то с помощью данного компонента
можно без труда перенести данные (например из файла) в специальные таблицы переводов.


Установка
---------

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require nepster-web/yii2-DbMessageImporter: dev-master
```

или добавьте

```
"nepster-web/yii2-DbMessageImporter": "dev-master"
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


  Пример обновление базы данных переводов:

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

  ~~~
  use nepster\yii2components\DbMessageImporter;
  ...
  $yaml = Yaml::parse(file_get_contents('/path/to/file.yml'));
  $DbMessageImporter = new DbMessageImporter($yaml);
  $DbMessageImporter->setMessageTable('{{%language_messages}}');
  $DbMessageImporter->setSourceMessageTable('{{%language_source_messages}}');
  $DbMessageImporter->update(); // return true or false
  ~~~