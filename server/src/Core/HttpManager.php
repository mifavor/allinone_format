<?php

namespace Core;

class HttpManager
{
    private $logger;
    private $timeout = 10;

    public function __construct()
    {
        $this->logger = LogManager::getInstance();
    }

    public function get($url)
    {
        try {
            $this->logger->debug("Fetching URL: " . $url);

            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);

            $content = @file_get_contents($url, false, $context);
            if ($content === false) {
                $this->logger->debug("file_get_contents failed, trying curl");
                return $this->getCurl($url);
            }

            $this->logger->debug("Successfully fetched URL");
            return $content;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    private function getCurl($url)
    {
        try {
            if (!function_exists('curl_init')) {
                throw new \Exception('CURL is not installed');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $content = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception('CURL Error: ' . $error);
            }

            return $content;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
