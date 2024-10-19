<?php
header('Content-Type: text/html; charset=utf-8');

class M3uParser
{
    /**
     * $channelMerge - 是否合并相同频道，默认为0，即不合并相同频道
     * @var int
     */
    private $channelMerge = 0;

    /**
     * $dumpType - 输出格式 默认0=m3u 1=text
     * @var int
     */
    private $dumpType = 0;

    /**
     * $selfJumpUrl - 跳转地址，默认为空
     * @var string
     */
    private $selfJumpUrl = "";
    /**
     * $m3uFile - 存储m3u文件的URL地址
     * @var string
     */
    private $m3uFile;

    /**
     * $m3uData - 存储从m3u文件读取的原始数据
     * @var string
     */
    private $m3uData;

    /**
     * $m3uDataArray - 存储解析后的m3u文件数据，以数组形式
     * @var array
     */
    private $m3uDataArray = [];

    /**
     * $channelDescReplace - 存储频道描述中需要替换的字符串
     * @var array
     */
    private $channelDescReplace = [
        "cctv5p" => "CCTV5+",
        "cgtnen" => "CGTN",
        "CGTN-记录" => "CGTN记录",
        "cgtndoc" => "CGTN记录",
        "cgtnru" => "CGTN俄语",
        "cgtnfr" => "CGTN法语",
        "cgtnsp" => "CGTN西语",
        "cgtnar" => "CGTN阿语",
        "上海东方卫视" => "东方卫视",
        "凤凰卫视中文" => "凤凰卫视中文台",
        "凤凰卫视资讯" => "凤凰卫视资讯台",
        "凤凰卫视香港" => "凤凰卫视香港台",
    ];

    /**
     * $m3uDataArrFormat - 存储格式化后的m3u数据，以数组形式
     */
    private $m3uDataArrFormat = [];

    /**
     * $channelOldToNew - 存储旧频道名称与新频道名称之间的对应关系
     */
    private $channelGroupOldToNew = [
        "4K频道" => "4 K 8 K",
        "8K频道" => "4 K 8 K",
        "央视" => "央视频道",
        "卫视" => "卫视频道",
        "NewTV" => "数字频道",
        "iHOT" => "数字频道",
        "SiTV" => "数字频道",
        "咪咕" => "数字频道",
        "求索" => "数字频道",
        "教育" => "数字频道",
        "其他" => "数字频道",
        "北京" => "地方频道",
        "湖南" => "地方频道",
        "上海" => "地方频道",
    ];

    /**
     * 构造函数，用于初始化类实例时的m3u文件路径
     *
     * 该构造函数通过组合传入的主机名和端口号来形成一个m3u文件的URL该m3u文件
     * 通常用于定义一个播放列表，这里将其存储在类实例的m3uFile属性中
     *
     * @param string $allinone_host - 一体化服务器的主机名，用于构建m3u文件的URL
     * @param int $allinone_port - 一体化服务器的端口号，默认为35455，用于构建m3u文件的URL
     */
    public function __construct($allinone_host, $allinone_port = 35455, $channel_merge = 0, $dump_type = 0, $self_jump_url = "")
    {
        // 组合主机名和端口号来创建m3u文件的URL
        $this->m3uFile = "http://" . $allinone_host . ":" . $allinone_port . "/tv.m3u";
        // 设置频道合并
        $this->channelMerge = $channel_merge;
        // 设置输出格式
        $this->dumpType = $dump_type;
        // 设置跳转地址
        $this->selfJumpUrl = $self_jump_url;
    }

    /**
     * 获取m3u文件数据
     *
     * 该函数通过调用file_get_contents()函数来读取m3u文件并保存到m3uData属性中
     */
    public function getM3uData()
    {
        // 读取m3u文件并保存到m3uData属性中
        $this->m3uData = file_get_contents($this->m3uFile);
    }

    /**
     * 将m3u数据解析为数组
     *
     * 该函数通过正则表达式来解析m3u文件中的数据，并将解析结果保存到m3uDataArray属性中
     */
    public function parseM3uDataToArray()
    {
        $re = '/#EXTINF:(.+?),tvg-id="([^"]+)"\s+tvg-name="([^"]+)"\s+tvg-logo="([^"]+)"\s+group-title="([^"]+)",(.*)[\r\n]+((https?|rtmp):\/\/.*)[\r\n]+/';
        $m3uDataArrayCount = preg_match_all($re, $this->m3uData, $matches);

        $pattern = '/(' . implode('|', array_keys($this->channelDescReplace)) . ')/i';
        for ($i = 0; $i < $m3uDataArrayCount; $i++) {
            $tmpChannelDesc = str_replace("_", "-", $matches[6][$i]);
            $tmpChannelDesc = preg_replace('/(cctv-?\d{2})(\d)k/i', '$1-$2K', $tmpChannelDesc);
            if (preg_match($pattern, $tmpChannelDesc, $descMatches)) {
                $tmpChannelId = $this->channelDescReplace[$descMatches[0]];
                $tmpChannelDesc = str_replace($descMatches[0], $tmpChannelId, $tmpChannelDesc);
            } else {
                $tmpChannelId = strtoupper($matches[2][$i]);
            }
            $tmpChannelDesc = preg_replace('/CCTV-?/i', 'CCTV', $tmpChannelDesc);
            $this->m3uDataArray[$i] = [
                "inf" => $matches[1][$i],
                "id" => $tmpChannelId,
                "logo" => $matches[4][$i],
                "group" => $matches[5][$i],
                "desc" => $tmpChannelDesc,
                "url" => $matches[7][$i],
            ];
        }
    }

    public function formatM3uDataArray()
    {
        $this->m3uDataArrFormat = [];
        foreach ($this->m3uDataArray as $item) {
            // 根据原 group & desc 转换成临时分组
            $tmpChannelGroup = "其他";
            foreach ($this->channelGroupOldToNew as $groupOld => $groupNew) {
                if ($groupNew == "地方频道") {
                    if (stripos($item["group"], $groupOld) === 0 || stripos($item["desc"], $groupOld) === 0) {
                        $tmpChannelGroup = $groupOld;
                        break;
                    }
                } else {
                    if (stripos($item["group"], $groupOld) !== false || stripos($item["desc"], $groupOld) !== false) {
                        $tmpChannelGroup = $groupOld;
                        break;
                    }
                }
            }

            $tmpChannelDesc = $item["desc"];
            // 临时分组 desc 格式化逻辑
            switch ($tmpChannelGroup) {
                case '4K频道':
                case '8K频道':
                    if (stripos($item["id"], "cctv") !== false) {
                        $tmpChannelDesc = strtoupper($item["desc"]);
                    }
                    break;
                case "央视":
                    if ($this->channelMerge == 1) {
                        if (stripos($item["id"], "cctv") !== false || stripos($item["id"], "cgtn") !== false) {
                            $tmpChannelDesc = $item["id"];
                        }
                        $tmpChannelUrlInfo = preg_replace(['/' . $item['id'] . '-?/i', '/cctv-?\d+k?-?/i'], '', $item["desc"]);
                        if ($tmpChannelUrlInfo) {
                            $item["url"] = sprintf("%s%s%s%s", $this->selfJumpUrl, urlencode($item["url"]), "$",  $tmpChannelUrlInfo);
                        }
                    } else {
                        $tmpChannelDesc = strtoupper($item["desc"]);
                    }
                    break;
                case "卫视":
                    if ($this->channelMerge == 1) {
                        $tmpChannelDesc = $item["id"];
                        $tmpChannelUrlInfo = preg_replace('/' . $item['id'] . '-?/i', '', $item["desc"]);
                        if ($tmpChannelUrlInfo) {
                            $item["url"] = sprintf("%s%s%s%s", $this->selfJumpUrl, urlencode($item["url"]), "$",  $tmpChannelUrlInfo);
                        }
                    }
                    break;
                case "咪咕":
                    $tmpChannelDesc = str_replace("咪咕视频-8M1080-", "", $item["desc"]);
                    break;
                default:
                    break;
            }
            $this->m3uDataArrFormat[$tmpChannelGroup][] = [
                "inf" => $item["inf"],
                "id" => $item["id"],
                "logo" => $item["logo"],
                "group" => $this->channelGroupOldToNew[$tmpChannelGroup],
                "desc" => $tmpChannelDesc,
                "url" => $item["url"]
            ];
        }
        // 排序 8K频道
        if (isset($this->m3uDataArrFormat["8K频道"])) {
            $arrDesc  = array_column($this->m3uDataArrFormat["8K频道"], "desc");
            array_multisort($arrDesc, SORT_ASC, SORT_NATURAL, $this->m3uDataArrFormat["8K频道"]);
        }
    }

    private function dumpM3u()
    {
        $str = '#EXTM3U x-tvg-url="https://epg.v1.mk/fy.xml"' . PHP_EOL;
        foreach ($this->channelGroupOldToNew as $groupOld => $groupNew) {
            // var_dump($groupOld);
            if ($this->channelMerge && ($groupOld == "央视" || $groupOld == "卫视")) {
                $tmpM3uDataArrMerge = [];
                foreach ($this->m3uDataArrFormat[$groupOld] as $item) {
                    if (isset($tmpM3uDataArrMerge[$item["id"]])) {
                        $tmpM3uDataArrMerge[$item["id"]]["url"][] = $item["url"];
                    } else {
                        $tmpM3uDataArrMerge[$item["id"]] = [
                            "inf" => $item["inf"],
                            "id" => $item["id"],
                            "logo" => $item["logo"],
                            "group" => $groupNew,
                            "desc" => preg_replace('/(CCTV\d+K?)/', '$1', $item["id"]),
                            "url" => [$item["url"]]
                        ];
                    }
                }
                foreach ($tmpM3uDataArrMerge as $item) {
                    $str .= sprintf('#EXTINF:%s,tvg-id="%s" tvg-name="%s" tvg-logo="%s" group-title="%s",%s%s%s%s', $item["inf"], $item["id"], $item["id"], $item["logo"], $groupNew, $item["desc"], PHP_EOL, implode(PHP_EOL, $item["url"]), PHP_EOL);
                }
            } else {
                if (isset($this->m3uDataArrFormat[$groupOld])) {
                    foreach ($this->m3uDataArrFormat[$groupOld] as $item) {
                        $str .= sprintf('#EXTINF:%s,tvg-id="%s" tvg-name="%s" tvg-logo="%s" group-title="%s",%s%s%s%s', $item["inf"], $item["id"], $item["id"], $item["logo"], $groupNew, $item["desc"], PHP_EOL, $item["url"], PHP_EOL);
                    }
                }
            }
        }
        return $str;
    }

    private function dumpText()
    {
        $str = "";
        $lastGroupNew = "";
        foreach ($this->channelGroupOldToNew as $groupOld => $groupNew) {
            if (!isset($this->m3uDataArrFormat[$groupOld]) || count($this->m3uDataArrFormat[$groupOld]) == 0) {
                continue;
            }
            if ($groupNew != $lastGroupNew) {
                $str .=  sprintf("%s,#genre#%s", $groupNew, PHP_EOL);
                $lastGroupNew = $groupNew;
            }
            if ($this->channelMerge && ($groupOld == "央视" || $groupOld == "卫视")) {
                $tmpM3uDataArrMerge = [];
                foreach ($this->m3uDataArrFormat[$groupOld] as $item) {
                    if (isset($tmpM3uDataArrMerge[$item["id"]])) {
                        $tmpM3uDataArrMerge[$item["id"]]["url"][] = $item["url"];
                    } else {
                        $tmpM3uDataArrMerge[$item["id"]] = [
                            "inf" => $item["inf"],
                            "id" => $item["id"],
                            "logo" => $item["logo"],
                            "group" => $groupNew,
                            "desc" => preg_replace('/(CCTV\d+K?)/', '$1', $item["id"]),
                            "url" => [$item["url"]]
                        ];
                    }
                }
                foreach ($tmpM3uDataArrMerge as $item) {
                    $str .= sprintf("%s,%s%s", $item["desc"], implode("#", $item["url"]), PHP_EOL);
                }
            } else {
                foreach ($this->m3uDataArrFormat[$groupOld] as $item) {
                    $str .= sprintf("%s,%s%s", $item["desc"], $item["url"], PHP_EOL);
                }
            }
        }
        return $str;
    }

    public function dumpContents()
    {
        if ($this->dumpType == 1) {
            return $this->dumpText();
        }
        return $this->dumpM3u();
    }

    public function debug()
    {
        // echo "<pre>";
        // echo $this->m3uData;
        // echo "</pre>";
        echo json_encode($this->m3uDataArray, JSON_UNESCAPED_UNICODE);
        // echo json_encode($this->m3uDataArrFormat, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * $jump - 判断是否是需要跳转的链接
 */
$jump = (isset($_GET['j']) && $_GET["j"]) ? $_GET["j"] : '';
if ($jump) {
    $pos = strpos($jump, '$');
    if ($pos !== false) {
        $jump = substr($jump, 0, $pos);
    }
    $jump = urldecode($jump);
    $jump = str_replace("&amp;", "&", $jump);
    if ($jump) {
        header("Location: $jump");
        exit;
    }
}

/**
 * $host - allinone服务器的主机名，默认为当前服务器的主机名
 */
$host = (isset($_GET['h']) && $_GET["h"]) ? $_GET["h"] : $_SERVER['HTTP_HOST'];
if (($pos = strpos($host, ':')) !== false) {
    $host = substr($host, 0, $pos);
}
/**
 * $port - allinone服务器的端口号，默认为35455
 */
$port = (isset($_GET['p']) && $_GET["p"]) ? $_GET["p"] : 35455;
/**
 * $merge - 是否合并频道，默认为0=不合并 1=合并
 */
$merge = (isset($_GET['m']) && $_GET["m"] == 0) ? 0 : 1;

/**
 * $type - 输出类型 默认0=m3u 1=text
 */
$type = (isset($_GET['t']) && $_GET["t"] == 1) ? 1 : 0;
/**
 * $selfJumpUrl - 本页面的URL，用于跳转至本页面
 */
$selfJumpUrl = sprintf("%s://%s%s?j=", (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http', $_SERVER['HTTP_HOST'], $_SERVER['SCRIPT_NAME']);

$m3uParser = new M3uParser($host, $port, $merge, $type, $selfJumpUrl);

$m3uParser->getM3uData();
$m3uParser->parseM3uDataToArray();
$m3uParser->formatM3uDataArray();
// $m3uParser->debug();
echo $m3uParser->dumpContents();
