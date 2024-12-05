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

// 如果是 /tv.php 或 /tptv.php，交给 PHP 内置服务器处理
if (in_array($path, ['/tv.php', '/tptv.php'])) {
    return false;
}

// 尝试路由处理
$router = new Http\RouterController();
$result = $router->dispatch($method, $path, $query);

// 如果路由处理返回 false，则尝试处理静态文件
if ($result === false) {
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'public' . $path;

    // 默认访问 index.html
    if ($path === '/') {
        $file = rtrim($file, '/') . DIRECTORY_SEPARATOR . 'index.html';
    }
    // 兼容 windows 路径
    $file = str_replace('/', DIRECTORY_SEPARATOR, $file);

    if (file_exists($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mime_types = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'json' => 'application/json'
        ];

        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
            header('Content-Length: ' . filesize($file));
        }
        readfile($file);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
    }
}
