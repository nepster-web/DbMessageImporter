# Yii2 MESSAGES IMPORTER
Комнонент ипортирует данные переводов в базу данных или php файлы.

**Внимание**

Данный пакет был создан в личных целях для облегчения установки персональных модулей и расширений на базе Yii2.

## Установка

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require --prefer-dist nepster-web/yii2-messages-importer "*"
```

или добавьте

```
"nepster-web/yii2-messages-importer": "*"
```

в файл `composer.json` в секцию require.


## Настройка

Необходимо добавить в файл конфигурации консольного приложения следующую настройку:

```php
'controllerMap' => [
    ...
    'translate' => [
        'class' => 'nepster\messagesimporter\Translate',
        'YmlFiles' => [
            '@app/languages/users.yml',
        ],
        'config' => [
            'file' => [
                'translatePath' => '@app/messages'
            ],
            'db' => [
                'messageTable' => '{{%language_messages}}',
                'sourceMessageTable' => '{{%language_source_messages}}',
                'connection' => 'db',
            ]
        ],
    ],
],
```

## Запуск

```
yii translate --type=db
```

## Пример users.yml

```
"users":
  "USERNAME":
    ru: 'Логин'
    en: 'Username'
  "EMAIL":
    ru: 'E-MAIL'
    en: 'E-MAIL'
  "PHONE":
    ru: 'Телефон'
    en: 'Phone'
  "PASSWORD":
    ru: 'Пароль'
    en: 'Password'
```