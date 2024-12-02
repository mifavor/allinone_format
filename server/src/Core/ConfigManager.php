<?php

namespace Core;

class ConfigManager
{
    private static $instance = null;
    private $logger;
    private $config;
    private $configFile;
    private $httpManager;
    // 原始频道分组（不可变）
    private $originChannelGroup = [
        'CCTV48K',   // CCTV 4K/8K 频道
        '4K',        // 其他 4K 频道
        '8K',        // 其他 8K 频道
        'CCTV',      // CCTV 频道
        'CGTN',      // CGTN 频道
        '央视数字',   // 央视数字频道
        '卫视',      // 卫视频道
        '地方',      // 地方频道
        '教育',      // 教育频道
        '求索',      // 求索频道
        'NewTV',     // NewTV 频道
        'iHOT',      // iHOT 频道
        'SiTV',      // SiTV 频道
        '咪咕',      // 咪咕频道
        '其他'       // 其他频道
    ];

    // link_type 的 key 值
    private $linkTypeKey = [
        'ysptp',
        'itv',
        'tptv',
        'slive',
        'gdcucc',
        'cloudfront',
        'other',
    ];

    // 默认配置
    private $defaultConfig = [
        'tv_m3u_url' => '',
        'link_output_jump' => true, // 是否输出跳转链接
        'link_output_desc' => true, // 是否输出频道描述
        'link_type' => [
            'ysptp' => true,      // 央视频道
            'itv' => true,        // itv 频道
            'tptv' => true,       // tptv 频道
            'slive' => true,      // 直播频道
            'gdcucc' => true,     // 广电频道
            'cloudfront' => true, // CDN 频道
            'other' => true,      // 其他频道
        ],
        'output_channel_group' => [
            '4 K 8 K' => ['CCTV48K', '4K', '8K'],
            '央视频道' => ['CCTV', 'CGTN', '央视数字'],
            '卫视频道' => ['卫视'],
            '地方频道' => ['地方'],
            '数字频道' => ['教育', '求索', 'NewTV', 'iHOT', 'SiTV', '咪咕', '其他']
        ]
    ];

    private function __construct()
    {
        $this->logger = new LogManager();
        $this->httpManager = new HttpManager();
        $this->configFile = dirname(dirname(__DIR__)) . '/config/config.json';
        $this->loadConfig();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function validateField($config, $field, $defaultValue)
    {
        if (!isset($config[$field])) {
            return ['value' => $defaultValue, 'error' => null];
        }

        switch ($field) {
            case 'tv_m3u_url':
                if (empty($config[$field])) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'tv.m3u 地址不能为空'
                    ];
                }
                if (!filter_var($config[$field], FILTER_VALIDATE_URL)) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'tv.m3u 地址必须是有效的 URL'
                    ];
                }
                if (!preg_match('/^https?:\/\//i', $config[$field])) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'tv.m3u 地址必须以 http:// 或 https:// 开头'
                    ];
                }
                break;

            case 'link_output_jump':
            case 'link_output_desc':
                if (!is_bool($config[$field])) {
                    return [
                        'value' => $defaultValue,
                        'error' => "{$field} 必须为布尔值"
                    ];
                }
                break;

            case 'link_type':
                if (!is_array($config[$field])) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'link_type 配置格式错误，必须是对象'
                    ];
                }
                $diff = array_diff(array_keys($config[$field]), $this->linkTypeKey);
                if (!empty($diff)) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'link_type 配置中存在未定义的类型: ' . implode(', ', $diff)
                    ];
                }
                $enabledTypes = array_filter($config[$field]);
                if (empty($enabledTypes)) {
                    return [
                        'value' => $defaultValue,
                        'error' => '至少需要启用一个直播源类型'
                    ];
                }
                break;

            case 'output_channel_group':
                if (!is_array($config[$field])) {
                    return [
                        'value' => $defaultValue,
                        'error' => 'output_channel_group 配置格式错误，必须是对象'
                    ];
                }

                foreach ($config[$field] as $key => $groups) {
                    if (!is_string($key) || !is_array($groups)) {
                        return [
                            'value' => $defaultValue,
                            'error' => 'output_channel_group 配置格式错误，分组名必须是字符串，分组内容必须是数组'
                        ];
                    }

                    foreach ($groups as $group) {
                        if (!in_array($group, $this->originChannelGroup)) {
                            return [
                                'value' => $defaultValue,
                                'error' => "输出频道分组中存在未定义的原始频道分组: {$group}"
                            ];
                        }
                    }
                }
                break;
        }

        return ['value' => $config[$field], 'error' => null];
    }

    private function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $content = file_get_contents($this->configFile);
            if ($content === false) {
                @unlink($this->configFile);
                $this->logger->error('无法读取 config.json 配置文件');
                $this->config = $this->defaultConfig;
                return;
            }

            $config = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                @unlink($this->configFile);
                $this->logger->error('config.json 配置文件格式错误: ' . json_last_error_msg());
                $this->config = $this->defaultConfig;
                return;
            }

            $validatedConfig = [];
            foreach ($this->defaultConfig as $field => $defaultValue) {
                $result = $this->validateField($config, $field, $defaultValue);
                if ($result['error']) {
                    $this->logger->error($result['error']);
                }
                $validatedConfig[$field] = $result['value'];
            }

            $this->config = $validatedConfig;
        } else {
            $this->config = $this->defaultConfig;
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getOriginChannelGroup()
    {
        return $this->originChannelGroup;
    }

    public function updateConfig($newConfig)
    {
        $validatedConfig = [];
        $errors = [];
        foreach ($this->defaultConfig as $field => $defaultValue) {
            if (!isset($newConfig[$field])) {
                $errors[] = "缺少 {$field} 配置";
                continue;
            }
            $result = $this->validateField($newConfig, $field, $defaultValue);
            if ($result['error']) {
                $errors[] = $result['error'];
            }
            $validatedConfig[$field] = $result['value'];
        }

        if (!empty($errors)) {
            throw new \Exception(implode("\n", $errors));
        }
        // 检查 tv_m3u_url 链接的 fetch 返回值是否包含 #EXTM3U
        if (isset($validatedConfig['tv_m3u_url'])) {
            $content = $this->httpManager->fetchContent($validatedConfig['tv_m3u_url']);
            if ($content) {
                if (strpos($content, '#EXTM3U') === false) {
                    throw new \Exception('tv.m3u 地址返回内容不是 m3u 格式: ' . $validatedConfig['tv_m3u_url']);
                }
            } else {
                throw new \Exception('tv.m3u 地址无法访问: ' . $validatedConfig['tv_m3u_url']);
            }
        }
        $this->config = $validatedConfig;
        $this->saveConfig();
        return $this->config;
    }



    private function saveConfig()
    {
        $configDir = dirname($this->configFile);

        if (!is_dir($configDir)) {
            if (!mkdir($configDir, 0755, true)) {
                throw new \Exception('无法创建配置目录');
            }
        }

        if (file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new \Exception('无法保存配置文件');
        }
    }
}
