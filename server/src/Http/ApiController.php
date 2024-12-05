<?php

namespace Http;

use Core\ConfigManager;
use Core\LogManager;
use Core\HttpManager;

class ApiController
{
    private $configManager;
    private $logger;
    private $httpManager;

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->logger = new LogManager();
        $this->httpManager = new HttpManager();
    }

    public function getConfig()
    {
        try {
            $config = $this->configManager->getConfig();
            // 判断 $config['tv_m3u_url'] 如果为空就尝试检测
            if (empty($config['tv_m3u_url'])) {
                $testUrl = $this->httpManager->detectTvM3uUrl();
                if ($testUrl) {
                    $config['tv_m3u_url'] = $testUrl;
                }
            }
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
            $this->configManager->updateConfig($newConfig);
            $this->logger->info('配置更新成功');
            $this->sendJsonResponse(["message" => "配置更新成功"]);
        } catch (\Exception $e) {
            $this->logger->error('更新配置失败: ' . $e->getMessage());
            $this->sendJsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getChannelUrls()
    {
        try {
            $baseUrl = $this->httpManager->getBaseUrl();

            // 定义支持的格式
            $formats = [
                '/m3u/1' => 'm3u 聚合格式 1 适合 天光云影v3.x',
                '/m3u/2' => 'm3u 聚合格式 2 适合 大部分播放器',
                '/m3u/3' => 'm3u 普通格式',

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
