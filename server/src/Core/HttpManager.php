<?php

namespace Core;

class HttpManager
{
    private $logger;
    private $connectTimeout = 2;
    private $timeout = 3;

    public function __construct()
    {
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
        // 检查反向代理
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            $port = $protocol == 'https' ? 443 : 80;
        } else {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : ($protocol == 'https' ? 443 : 80);
        }

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
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
            // 检查反向代理
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                $port = $protocol == 'https' ? 443 : 80;
            } else {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : ($protocol == 'https' ? 443 : 80);
            }
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];

            if ($port == '35456') {
                $ip = explode(':', $host)[0];
                $testUrl = "$protocol://$ip:35455/tv.m3u";
                $this->logger->debug('尝试检测 tv.m3u url: ' . $testUrl);
                $content = $this->fetchContent($testUrl);
                if ($content && strpos($content, '#EXTM3U') !== false) {
                    $this->logger->info('自动检测到 tv.m3u url: ' . $testUrl);
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
