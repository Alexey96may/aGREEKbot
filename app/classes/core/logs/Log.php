<?php
namespace App\Classes\Core\Logs;

class Log
{
    private static $rootPathDir;
    private $pathLog;
    const NEW_LOG_MESSAGE = '--- NEW LOG ---';

    public function __construct(string $path_value)
    {
        if (empty(self::$rootPathDir)) {
            throw new \Exception("You must set the root dir for the logs");
        }

        $path = $this->getValidPath($path_value);
        $this->pathLog = self::$rootPathDir . '/' . $path;

        if (!file_exists($this->pathLog)) {
            $arrayPath = explode('/', $path);
            $accumPathString = self::$rootPathDir . '/';

            foreach ($arrayPath as $key => $value) {
                $accumPathString .= $value.'/';

                if (file_exists($accumPathString)) {
                    continue;
                }
                if ($key === count($arrayPath) - 1) {
                    continue;
                }

                // mkdir(self::$rootPathDir);
                mkdir($accumPathString, 0777, true);
            }
        }
    }

    public static function setPathByClass(string $path_class): Log
    {
        return new Log($path_class.'.log');
    }

    public static function setPathByMethod(string $path_method): Log
    {
        $path_method = str_replace('::', '/', $path_method);
        return new Log($path_method.'.log');
    }

    public function log(string $log_text)
    {
        $file = fopen($this->pathLog, 'a+');
        $message = PHP_EOL . PHP_EOL . self::NEW_LOG_MESSAGE . PHP_EOL . date('Y.m.d h:i:s') . PHP_EOL . $log_text;
        fwrite($file, $message);
        fclose($file);
    }

    public static function setRootLogDir(string $root_path)
    {
        self::$rootPathDir = $root_path;
    }

    public function getValidPath(string $path_value): string
    {
        $path = trim(str_replace('\\', '/', $path_value), '/');
        return $path;
    }
}