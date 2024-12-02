<?php

namespace Core;

class M3uParser
{
    private $logger;
    private $pattern = '/#EXTINF:(.+?)[,\s]+tvg-id="([^"]+)"\s+tvg-name="[^"]+"\s+tvg-logo="([^"]+)"\s+group-title="([^"]+)",(.*)[\r\n]+((https?|rtmp):\/\/.*)[\r\n]+/';

    public function __construct()
    {
        $this->logger = new LogManager();
    }

    public function parse($content)
    {
        try {
            if (empty($content)) {
                throw new \Exception('M3U解析内容为空');
            }

            $matches = [];
            preg_match_all($this->pattern, $content, $matches, PREG_SET_ORDER);

            if (empty($matches)) {
                throw new \Exception('未解析到有效的M3U条目');
            }

            $result = [];
            foreach ($matches as $match) {
                $result[] = [
                    'inf' => trim($match[1]),
                    'id' => trim($match[2]),
                    'logo' => trim($match[3]),
                    'group' => trim($match[4]),
                    'desc' => trim($match[5]),
                    'link' => trim($match[6])
                ];
            }

            $this->logger->info('M3U解析完成, 发现 ' . count($result) . ' 个条目');
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('M3U解析失败: ' . $e->getMessage());
            return false;
        }
    }
}
