<?php

namespace nepster\messagesimporter;

use yii\base\InvalidParamException;
use yii\helpers\Console;
use Yii;

/**
 * FileMessageImporter импортирует данные переводов в php каталоги
 */
class FileMessageImporter
{
    /**
     * @var string Путь к папке для переводов
     */
    private $_translatePath;

    /**
     * init
     */
    public function __construct($translatePath)
    {
        $this->_translatePath = $translatePath;
    }

    /**
     * Import translations
     * @param array $translateArray
     * @return array
     */
    public function import(array $translateArray)
    {
        $translates = [];

        // Получаем данные переводов в необходимом формате
        foreach ($translateArray as $category => $constants) {
            foreach ($constants as $constant => $messages) {
                foreach ($messages as $lang => $message) {
                    $translates[$lang][$category][$constant] = $message;
                }
            }
        }

        $translatePath = Yii::getAlias($this->_translatePath . '/');
        $template = '<?php ' . PHP_EOL . 'return [{content}];';

        $result = [];

        // Создание файлов
        foreach ($translates as $lang => $categories) {
            foreach ($categories as $category => $messages) {
                $content = '';
                foreach ($messages as $constant => $message) {
                    $message = str_replace("'", '`', $message);
                    $content .= "\t" . "'$constant' => " . "'$message'" . ',' . PHP_EOL;
                }
                $file = $lang . DIRECTORY_SEPARATOR . $category . '.php';
                $content = str_replace('{content}', PHP_EOL . $content, $template);
                $save = $this->save($translatePath . $file, $content);
                $result[$translatePath . $file] = $save;
            }
        }

        return $result;
    }

    /**
     * Saves the code into the file specified by [[path]].
     * Taken/modified from yii\gii\CodeFile
     *
     * @param string $path
     * @param string $content
     * @return string|boolean the error occurred while saving the code file, or true if no error.
     */
    protected function save($path, $content)
    {
        $newDirMode = 0755;
        $newFileMode = 0644;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $mask = @umask(0);
            $result = @mkdir($dir, $newDirMode, true);
            @umask($mask);
            if (!$result) {
                return "Unable to create the directory '$dir'.";
            }
        }
        if (@file_put_contents($path, $content) === false) {
            return "Unable to write the file '{$path}'.";
        } else {
            $mask = @umask(0);
            @chmod($path, $newFileMode);
            @umask($mask);
        }
        return true;
    }
}
