<?php

namespace Http;

use Core\LogManager;

class RouterController
{
    private $logger;
    private $routes = [];

    public function __construct()
    {
        $this->logger = new LogManager();
        // 注册 API 路由
        $this->routes['/api/config'] = [
            'GET' => [ApiController::class, 'getConfig'],
            'POST' => [ApiController::class, 'updateConfig']
        ];
        $this->routes['/api/channel-urls'] = [
            'GET' => [ApiController::class, 'getChannelUrls']
        ];

        // 注册 M3U 和 TXT 路由
        $this->routes['#/m3u(/\d+)?/?$#'] = [
            'GET' => [HttpController::class, 'm3u']
        ];
        $this->routes['#/txt(/\d+)?/?$#'] = [
            'GET' => [HttpController::class, 'txt']
        ];

        // 注册跳转路由
        $this->routes['/jump'] = [
            'GET' => [HttpController::class, 'jump']
        ];

        // 注册调试路由
        $this->routes['/debug'] = [
            'GET' => [HttpController::class, 'debug']
        ];
    }

    public function dispatch($method, $path, $query)
    {
        $this->logger->info($method . ' ' . $path . ($query ? '?' . $query : ''));

        // 修改精确匹配为模糊匹配
        foreach ($this->routes as $routePath => $handlers) {
            // 如果是正则表达式路由，使用 preg_match
            if ($routePath[0] === '#') {
                if (preg_match($routePath, $path, $matches)) {
                    if (isset($handlers[$method])) {
                        [$class, $action] = $handlers[$method];
                        $controller = new $class();
                        $format = isset($matches[1]) ? trim($matches[1], '/') : '1';
                        return $controller->$action($format, $_GET);
                    }
                }
            }
            // 否则使用字符串模糊匹配
            else if (strpos($path, $routePath) !== false) {
                if (isset($handlers[$method])) {
                    [$class, $action] = $handlers[$method];
                    $controller = new $class();
                    return $controller->$action();
                }
            }
        }

        return false; // 没有匹配的路由
    }
}
