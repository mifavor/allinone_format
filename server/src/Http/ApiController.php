<?php

namespace Http;

use Core\ConfigManager;
use Core\LogManager;

class ApiController
{
    private $configManager;
    private $logger;

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->logger = LogManager::getInstance();
    }

    public function getConfig()
    {
        try {
            $config = $this->configManager->getConfig();
            $config['origin_channel_group'] = $this->configManager->getOriginChannelGroup();
            $this->sendJsonResponse($config);
        } catch (\Exception $e) {
            $this->logger->error('Get config failed: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function updateConfig()
    {
        try {
            $input = file_get_contents('php://input');
            $newConfig = json_decode($input, true);

            if (!$newConfig) {
                throw new \Exception('Invalid JSON data');
            }
            if (isset($newConfig['origin_channel_group'])) {
                unset($newConfig['origin_channel_group']);
            }
            $config = $this->configManager->updateConfig($newConfig);
            $this->logger->info('Config updated successfully');
            $config['origin_channel_group'] = $this->configManager->getOriginChannelGroup();
            $this->sendJsonResponse($config);
        } catch (\Exception $e) {
            $this->logger->error('Update config failed: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getChannelUrls()
    {
        try {
            // 获取当前请求的协议
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

            // 获取当前请求的主机和端口
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];

            // 如果是标准端口，则不显示端口号
            $isStandardPort = ($protocol === 'http' && $port == 80) ||
                ($protocol === 'https' && $port == 443);

            // 如果主机中已包含端口且不是标准端口，使用原始主机名
            // 否则根据是否是标准端口决定是否添加端口号
            if (!strpos($host, ':')) {
                $host = $isStandardPort ? $host : $host . ':' . $port;
            }

            // 基础URL
            $baseUrl = $protocol . '://' . $host;

            // 定义支持的格式
            $formats = [
                '/m3u' => 'm3u 聚合格式 等同于 /m3u/1',
                '/m3u/1' => 'm3u 聚合格式 1 适合 天光云影v3.x',
                '/m3u/2' => 'm3u 聚合格式 2 适合 大部分播放器',
                '/m3u/3' => 'm3u 普通格式',

                '/txt' => 'txt 聚合格式 等同于 /txt/1',
                '/txt/1' => 'txt 聚合格式 1',
                '/txt/2' => 'txt 聚合格式 2',
                '/txt/3' => 'txt 普通格式'
            ];

            // 生成所有URL
            $urls = [];

            foreach ($formats as $path => $description) {
                $urls[] = ['url' => $baseUrl . $path, 'desc' => $description];
            }

            $this->sendJsonResponse($urls);
        } catch (\Exception $e) {
            $this->logger->error('Get API formats failed: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function sendJsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
}
