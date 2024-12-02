<?php

namespace Core;

class ChannelManager
{
    private $configManager;
    private $logger;
    private $nameReplacements = [
        '_' => '-',
        'cctv5p' => 'CCTV5+',
        "cctv5plus" => "CCTV5+",
        'cgtnen' => 'CGTN',
        'CGTN-记录' => 'CGTN纪录',
        'cgtndoc' => 'CGTN纪录',
        'cgtnru' => 'CGTN俄语',
        'cgtnfr' => 'CGTN法语',
        'cgtnsp' => 'CGTN西语',
        'cgtnar' => 'CGTN阿语',
        '上海东方卫视' => '东方卫视',
        '凤凰卫视中文' => '凤凰卫视中文台',
        '凤凰卫视资讯' => '凤凰卫视资讯台',
        '凤凰卫视香港' => '凤凰卫视香港台',
        "newtv炫舞未来" => "NewTV炫舞未来",
    ];

    private $wsChannels = [
        // 港澳台地区
        "凤凰卫视中文台",
        "凤凰卫视资讯台",
        "凤凰卫视香港台",
        "香港卫视",
        "澳门卫视",
        "台湾卫视",

        // 一线卫视（最受欢迎）
        "湖南卫视",
        "浙江卫视",
        "江苏卫视",
        "东方卫视",

        // 二线卫视（较受欢迎）
        "北京卫视",
        "深圳卫视",
        "广东卫视",
        "山东卫视",
        "天津卫视",
        "东南卫视",
        "湖北卫视",
        "安徽卫视",

        // 三线卫视
        "重庆卫视",
        "黑龙江卫视",
        "辽宁卫视",
        "江西卫视",
        "河北卫视",
        "河南卫视",
        "四川卫视",
        "广西卫视",

        // 其他省级卫视
        "云南卫视",
        "贵州卫视",
        "山西卫视",
        "陕西卫视",
        "吉林卫视",
        "海南卫视",
        "甘肃卫视",
        "内蒙古卫视",
        "宁夏卫视",
        "青海卫视",
        "西藏卫视",
        "新疆卫视",
        "兵团卫视"
    ];

    private $cctvOrder = [
        "CCTV4K",
        "CCTV8K",
        "CCTV1",
        "CCTV2",
        "CCTV3",
        "CCTV4",
        "CCTV5",
        "CCTV5+",
        "CCTV6",
        "CCTV7",
        "CCTV8",
        "CCTV9",
        "CCTV10",
        "CCTV11",
        "CCTV12",
        "CCTV13",
        "CCTV14",
        "CCTV15",
        "CCTV16",
        "CCTV17"
    ];

    public function __construct()
    {
        $this->configManager = ConfigManager::getInstance();
        $this->logger = new LogManager();
    }

    public function format($m3uData)
    {
        try {
            // 按原始频道分组格式化
            $originGrouped = $this->formatOriginChannelGroup($m3uData);
            if (!$originGrouped) {
                throw new \Exception('按原始频道分组格式化失败');
            }
            // $this->logger->debug("按原始频道分组格式化完成");
            // $this->logger->debug(json_encode($originGrouped, JSON_UNESCAPED_UNICODE));

            // 按输出频道分组格式化
            $outputGrouped = $this->formatOutputChannelGroup($originGrouped);
            if (!$outputGrouped) {
                throw new \Exception('按输出频道分组格式化失败');
            }
            // $this->logger->debug("按输出频道分组格式化完成");
            // $this->logger->debug(json_encode($outputGrouped, JSON_UNESCAPED_UNICODE));

            return $outputGrouped;
        } catch (\Exception $e) {
            $this->logger->error('频道格式化失败: ' . $e->getMessage());
            return false;
        }
    }

    private function formatOriginChannelGroup($m3uData)
    {
        $config = $this->configManager->getConfig();
        $result = [];

        foreach ($m3uData as $item) {
            // 替换频道名称
            $desc = $this->replaceChannelName($item['desc']);
            // 获取链接类型
            $linkType = $this->getLinkType($item['link']);

            if (!isset($config['link_type'][$linkType]) || !$config['link_type'][$linkType]) {
                $this->logger->debug("跳过: 链接类型 {$linkType} 已禁用 " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }

            // 先确定频道分组
            $group = $this->determineChannelGroup($item, $desc);
            if (!$group) {
                $this->logger->debug("跳过: 未找到匹配的分组 " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }

            // 根据分组处理频道信息
            $channelInfo = $this->processChannelInfo($item, $desc, $linkType, $group);
            if (!$channelInfo) {
                $this->logger->debug("跳过: 频道信息处理失败 " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }

            // 确保 $result 存在 $group 分组
            if (!isset($result[$group])) {
                $result[$group] = [];
            }

            // 将频道添加到对应分组
            $this->addToGroup($result[$group], $channelInfo);
        }

        return $result;
    }

    private function formatOutputChannelGroup($originGrouped)
    {
        $config = $this->configManager->getConfig();
        $result = [];

        foreach ($config['output_channel_group'] as $outputGroup => $originGroups) {
            $result[$outputGroup] = [];
            foreach ($originGroups as $originGroup) {
                if (isset($originGrouped[$originGroup])) {
                    $result[$outputGroup] = array_merge(
                        $result[$outputGroup],
                        $originGrouped[$originGroup]
                    );
                }
            }
        }

        return $result;
    }

    private function replaceChannelName($name)
    {
        foreach ($this->nameReplacements as $from => $to) {
            $name = str_ireplace($from, $to, $name);
        }
        return $name;
    }

    private function getLinkType($link)
    {
        $config = $this->configManager->getConfig();
        foreach ($config['link_type'] as $linkType => $enabled) {
            if (strpos($link, $linkType) !== false) {
                return $linkType;
            }
        }

        return 'other';
    }

    private function determineChannelGroup($item, $desc)
    {
        // CCTV48K 分组
        // 匹配 CCTV-?[4|8]K
        if (preg_match('/cctv-?([4|8])k/i', $desc)) {
            return 'CCTV48K';
        }
        // 匹配 cctv-?\d\d?-?[4|8]k
        if (preg_match('/cctv-?\d+-?[4|8]k/i', $desc)) {
            return 'CCTV48K';
        }

        // 4K
        if (stripos($desc, '4k') !== false && !preg_match('/cctv/i', $desc)) {
            return '4K';
        }

        // 8K
        if (stripos($desc, '8k') !== false && !preg_match('/cctv/i', $desc)) {
            return '8K';
        }

        // CCTV
        if (preg_match('/cctv-?([1-9]\d?\+?)/i', $desc)) {
            return 'CCTV';
        }

        // CGTN
        if (preg_match('/cgtn([^-]*)/i', $desc)) {
            return 'CGTN';
        }

        // 央视数字频道
        if ($item['group'] === '央视' && !preg_match('/(CCTV|CGTN)/i', $desc)) {
            return '央视数字';
        }

        // 卫视频道
        foreach ($this->wsChannels as $ws) {
            if (stripos($desc, $ws) !== false) {
                return '卫视';
            }
        }

        // 地方频道
        if (preg_match('/^(北京|上海|南京|湖南|徐州|常州|睢宁|南通|伊春|镇江|宿迁|邳州|赣榆|江苏)/i', $desc)) {
            return '地方';
        }

        // 教育频道
        if (stripos($desc, '教育') !== false) {
            return '教育';
        }

        // 数字频道分组
        $digitalChannels = ['求索', 'NewTV', 'iHOT', 'SiTV', '咪咕'];
        foreach ($digitalChannels as $dc) {
            if (stripos($desc, $dc) === 0 || stripos($item['group'], $dc) === 0) {
                return $dc;
            }
        }

        // 其他频道
        return '其他';
    }

    private function processChannelInfo($item, $desc, $linkType, $group)
    {
        switch ($group) {
            case 'CCTV48K':
                // 匹配 CCTV-?[4|8]K
                if (preg_match('/cctv-?([4|8])k-?(.*)/i', $desc, $matches)) {
                    $channelNum = $matches[1];
                    $extraDesc = isset($matches[2]) ? $matches[2] : '';
                    $channelName = 'CCTV' . $channelNum . 'K';
                    return [
                        'inf' => $item['inf'],
                        'id' => $channelName,
                        'logo' => $item['logo'],
                        'name' => $extraDesc ? $channelName . '-' . $extraDesc : $channelName,
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => $extraDesc
                        ]]
                    ];
                }
                // 匹配 cctv-?\d\d?-?[4|8]k
                if (preg_match('/cctv-?(\d+)-?([4|8])k-?(.*)/i', $desc, $matches)) {
                    $channelNum = $matches[1];
                    $k = $matches[2];
                    $extraDesc = isset($matches[3]) ? $matches[3] : '';
                    return [
                        'inf' => $item['inf'],
                        'id' => 'CCTV' . $channelNum,
                        'logo' => $item['logo'],
                        'name' => 'CCTV' . $channelNum . '-' . $k . 'K' . ($extraDesc ? '-' . $extraDesc : ''),
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => $extraDesc
                        ]]
                    ];
                }
                break;

            case 'CCTV':
                if (preg_match('/cctv-?([1-9]\d?\+?)/i', $desc, $matches)) {
                    $channelNum = $matches[1];
                    $channelName = 'CCTV' . $channelNum;
                    return [
                        'inf' => $item['inf'],
                        'id' => $channelName,
                        'logo' => $item['logo'],
                        'name' => $channelName,
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => preg_replace('/cctv-?[1-9]\d?\+?-?/i', '', $desc)
                        ]]
                    ];
                }
                break;

            case 'CGTN':
                if (preg_match('/cgtn([^-]*)/i', $desc, $matches)) {
                    $channelName = 'CGTN' . $matches[1];
                    return [
                        'inf' => $item['inf'],
                        'id' => $channelName,
                        'logo' => $item['logo'],
                        'name' => $channelName,
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => preg_replace('/cgtn[^-]*-?/i', '', $desc)
                        ]]
                    ];
                }
                break;

            case '央视数字':
                return [
                    'inf' => $item['inf'],
                    'id' => $desc,
                    'logo' => $item['logo'],
                    'name' => $desc,
                    'urls' => [[
                        'link' => $item['link'],
                        'type' => $linkType,
                        'desc' => ''
                    ]]
                ];

            case '卫视':
                foreach ($this->wsChannels as $ws) {
                    if (stripos($desc, $ws) !== false) {
                        $extraDesc = '';
                        if (preg_match('/-(.*)$/', $desc, $matches)) {
                            $extraDesc = $matches[1];
                        }
                        return [
                            'inf' => $item['inf'],
                            'id' => $ws,
                            'logo' => $item['logo'],
                            'name' => $ws,
                            'urls' => [[
                                'link' => $item['link'],
                                'type' => $linkType,
                                'desc' => $extraDesc
                            ]]
                        ];
                    }
                }
                break;

            case '地方':
                $extraDesc = '';
                if (preg_match('/-(.*)$/', $desc, $matches)) {
                    $extraDesc = $matches[1];
                }
                return [
                    'inf' => $item['inf'],
                    'id' => $desc,
                    'logo' => $item['logo'],
                    'name' => $desc,
                    'urls' => [[
                        'link' => $item['link'],
                        'type' => $linkType,
                        'desc' => $extraDesc
                    ]]
                ];

            case '咪咕':
                if (preg_match('/咪咕视频_?-?8M1080_?-?/i', $desc)) {
                    return [
                        'inf' => $item['inf'],
                        'id' => '咪咕4K',
                        'logo' => $item['logo'],
                        'name' => preg_replace('/咪咕视频_?-?8M1080_?-?/i', '', $desc),
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => ''
                        ]]
                    ];
                } else {
                    return [
                        'inf' => $item['inf'],
                        'id' => $desc,
                        'logo' => $item['logo'],
                        'name' => $desc,
                        'urls' => [[
                            'link' => $item['link'],
                            'type' => $linkType,
                            'desc' => ''
                        ]]
                    ];
                }

            case '求索':
            case 'NewTV':
            case 'iHOT':
            case 'SiTV':
                return [
                    'inf' => $item['inf'],
                    'id' => $desc,
                    'logo' => $item['logo'],
                    'name' => $desc,
                    'urls' => [[
                        'link' => $item['link'],
                        'type' => $linkType,
                        'desc' => ''
                    ]]
                ];

            default:
                return [
                    'inf' => $item['inf'],
                    'id' => $item['id'],
                    'logo' => $item['logo'],
                    'name' => $desc,
                    'urls' => [[
                        'link' => $item['link'],
                        'type' => $linkType,
                        'desc' => ''
                    ]]
                ];
        }
    }

    private function addToGroup(&$group, $channelInfo)
    {
        // 确保 $group 是数组
        if (!is_array($group)) {
            $group = [];
        }

        // 查找是否已存在相同频道
        foreach ($group as &$existing) {
            if ($existing['name'] === $channelInfo['name']) {
                // 合并URLs
                $existing['urls'] = array_merge(
                    $existing['urls'],
                    $channelInfo['urls']
                );

                // 按link_type key排序
                $config = $this->configManager->getConfig();
                $linkTypeSort = array_keys($config['link_type']);
                usort($existing['urls'], function ($a, $b) use ($linkTypeSort) {
                    $aIndex = array_search($a['type'], $linkTypeSort);
                    $bIndex = array_search($b['type'], $linkTypeSort);
                    return $aIndex - $bIndex;
                });

                return;
            }
        }

        // 如果是CCTV48K或CCTV分组，需要按照预定义顺序排序
        if (stripos($channelInfo['id'], 'CCTV') === 0) {
            $insertIndex = $this->findCCTVInsertIndex($group, $channelInfo);
            array_splice($group, $insertIndex, 0, [$channelInfo]);
            return;
        }

        // 如果是卫视频道，需要按照预定义顺序排序
        if (in_array($channelInfo['id'], $this->wsChannels)) {
            $insertIndex = $this->findWSInsertIndex($group, $channelInfo);
            array_splice($group, $insertIndex, 0, [$channelInfo]);
            return;
        }

        // 其他频道直接添加到末尾
        $group[] = $channelInfo;
    }

    private function findCCTVInsertIndex($group, $channelInfo)
    {
        // 在预定义顺序中查找目标频道的位置
        $targetIndex = array_search($channelInfo['id'], $this->cctvOrder);

        // 如果频道不在预定义顺序中，添加到末尾
        if ($targetIndex === false) {
            return count($group);
        }

        // 获取配置中的 linkTypeKeys
        $config = $this->configManager->getConfig();
        $linkTypeKeys = array_keys($config['link_type']);

        // 遍历现有分组，找到合适的插入位置
        for ($i = 0; $i < count($group); $i++) {
            $currentId = $group[$i]['id'];
            $currentIndex = array_search($currentId, $this->cctvOrder);

            if ($currentIndex === false) {
                // 当前频道不在预定义顺序中，新频道应该插入在这个位置
                return $i;
            } elseif ($currentIndex > $targetIndex) {
                // 当前频道的顺序在目标频道之后，新频道应该插入在这个位置
                return $i;
            } elseif ($currentIndex === $targetIndex) {
                // ID相同，比较linkType
                $currentLinkType = $group[$i]['urls'][0]['type'];
                $newLinkType = $channelInfo['urls'][0]['type'];

                $currentTypeIndex = array_search($currentLinkType, $linkTypeKeys);
                $newTypeIndex = array_search($newLinkType, $linkTypeKeys);

                if ($newTypeIndex < $currentTypeIndex) {
                    // 新频道的linkType应该排在前面
                    return $i;
                }
            }
        }

        // 如果没有找到合适的位置，添加到末尾
        return count($group);
    }

    private function findWSInsertIndex($group, $channelInfo)
    {
        // 在预定义顺序中查找目标频道的位置
        $targetIndex = array_search($channelInfo['id'], $this->wsChannels);

        // 如果频道不在预定义顺序中，添加到末尾
        if ($targetIndex === false) {
            return count($group);
        }

        // 获取配置中的 linkTypeKeys
        $config = $this->configManager->getConfig();
        $linkTypeKeys = array_keys($config['link_type']);

        // 遍历现有分组，找到合适的插入位置
        for ($i = 0; $i < count($group); $i++) {
            $currentId = $group[$i]['id'];
            $currentIndex = array_search($currentId, $this->wsChannels);

            if ($currentIndex === false) {
                // 当前频道不在预定义顺序中，新频道应该插入在这个位置
                return $i;
            } elseif ($currentIndex > $targetIndex) {
                // 当前频道的顺序在目标频道之后，新频道应该插入在这个位置
                return $i;
            } elseif ($currentIndex === $targetIndex) {
                // ID相同，比较linkType
                $currentLinkType = $group[$i]['urls'][0]['type'];
                $newLinkType = $channelInfo['urls'][0]['type'];

                $currentTypeIndex = array_search($currentLinkType, $linkTypeKeys);
                $newTypeIndex = array_search($newLinkType, $linkTypeKeys);

                if ($newTypeIndex < $currentTypeIndex) {
                    // 新频道的linkType应该排在前面
                    return $i;
                }
            }
        }

        // 如果没有找到合适的位置，添加到末尾
        return count($group);
    }
}
