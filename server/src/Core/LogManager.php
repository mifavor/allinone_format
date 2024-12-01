<?php

namespace Core;

class LogManager
{
    private static $instance = null;

    private function __construct()
    {
        // 只配置日志输出到 stderr
        ini_set('error_log', 'php://stderr');
        ini_set('log_errors', 1);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function log($message, $level)
    {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[{$date}] [{$level}] {$message}";

        // 使用 error_log 写入日志
        // 在 Docker 环境中会写入到容器日志
        // 在 PHP 内置服务器中会写入到控制台
        error_log($logMessage);
    }

    public function error($message)
    {
        $this->log($message, 'ERROR');
    }

    public function info($message)
    {
        $this->log($message, 'INFO');
    }

    public function debug($message)
    {
        $this->log($message, 'DEBUG');
    }

    public function warning($message)
    {
        $this->log($message, 'WARNING');
    }
}
