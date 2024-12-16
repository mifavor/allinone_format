<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('error_log', 'php://stderr'); // 日志输出到 stderr
ini_set('log_errors', 1); // 开启 error 日志记录
ini_set('display_errors', 0); // 不显示错误
// ini_set('display_errors', 1); // 显示错误 用于调试

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 自动加载类
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/src/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// 获取请求信息
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

// 尝试路由处理
$router = new Http\RouterController();
$result = $router->dispatch($method, $path, $query);

// 如果路由处理返回 false，则尝试处理静态文件
if ($result === false) {
    if ($path === '/') {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.html';
        header('Content-Type: text/html');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    } else {
        return false;
    }
}
