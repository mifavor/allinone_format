<?php

namespace Core;

class HttpManager
{
    private $configManager;
    private $logger;
    private $connectTimeout = 2;
    private $timeout = 3;

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->logger = new LogManager();
    }

    public function fetchContent($url)
    {
        try {
            $this->logger->debug("Fetching URL: " . $url);

            if (!function_exists('curl_init')) {
                throw new \Exception('CURL is not installed');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout); // TCP 握手超时
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); // 整体请求超时
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $content = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($error) {
                throw new \Exception('CURL Error: ' . $error);
            }

            if ($httpCode === 200 && $content) {
                return $content;
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Fetch content failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        $isStandardPort = ($protocol === 'http' && $port == 80) ||
            ($protocol === 'https' && $port == 443);
        if (!strpos($host, ':')) {
            $host = $isStandardPort ? $host : $host . ':' . $port;
        }
        return $protocol . '://' . $host;
    }

    public function detectTvM3uUrl(&$content = null)
    {
        try {
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];

            if ($port == '35456') {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $ip = explode(':', $host)[0];
                $testUrl = "$protocol://$ip:35455/tv.m3u";
                $this->logger->debug('尝试检测 tv.m3u url: ' . $testUrl);
                $content = $this->fetchContent($testUrl);
                if ($content && strpos($content, '#EXTM3U') !== false) {
                    $this->logger->info('自动检测到 tv.m3u url: ' . $testUrl);
                    // 更新配置
                    $this->configManager->updateConfig(['tv_m3u_url' => $testUrl]);
                    return $testUrl;
                }
            }
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Detect tv.m3u url failed: ' . $e->getMessage());
            return false;
        }
    }
}
