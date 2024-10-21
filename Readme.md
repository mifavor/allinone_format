## 功能简介：
本项目是对 https://hub.docker.com/r/youshandefeiyang/allinone 项目返回 tv.m3u / tptv.m3u 内容进行二次频道分组&频道名格式化。所以安装本项目前，请先安装 youshandefeiyang/allinone 项目。

## docker 运行方式（推荐）：
```text
docker run -d --restart=always -p 35456:35456 --name allinone_format yuexuangu/allinone_format:latest
```

## 请求参数 (tv.php 对 tv.m3u 的二次处理结果)
```text
http://内网IP:35456/tv.php?h=allinoneIP&p=allinoneHost&m=1&t=0

请求参数说明：
h  可选参数  allinone 项目部署的内外网 IP或域名（不能使用 127.0.0.1），默认值 = 请求的内网IP
p  可选参数  allinone 项目部署的端口, 默认值 = 35455
m  可选参数  是否对频道连接进行聚合，默认值 1=聚合（强烈推荐） 0=不聚合（仅推荐不支持聚合格式的壳子使用）
t  可选参数  输出格式 默认值 0=m3u， 1=text

请求例子：
http://192.168.31.50:35456/tv.php
上面请求等同于
http://192.168.31.50:35456/tv.php?h=192.168.31.50&p=35455&m=1&t=0
```

## 请求参数 (tptv.php 对 tptv.m3u 的二次处理结果)
```text
http://内网IP:35456/tptv.php?h=allinoneIP&p=allinoneHost&t=0

请求参数说明：
h  可选参数  allinone 项目部署的内外网 IP或域名（不能使用 127.0.0.1），默认值 = 请求的内网IP
p  可选参数  allinone 项目部署的端口, 默认值 = 35455
t  可选参数  输出格式 默认值 0=m3u， 1=text

请求例子：
http://192.168.31.50:35456/tptv.php
上面请求等同于
http://192.168.31.50:35456/tptv.php?h=192.168.31.50&p=35455&t=0
```
## 请求参数 (migu.php)
```text
http://内网IP:35456/migu.php?t=0

请求参数说明：
t  可选参数  输出格式 默认值 0=m3u， 1=text

请求例子：
http://192.168.31.50:35456/migu.php
上面请求等同于
http://192.168.31.50:35456/migu.php?t=0
```

## 项目源码(有 php-fpm 环境的可以直接运行源码中的 tv.php ):
[https://github.com/FanchangWang/allinone_format](https://github.com/FanchangWang/allinone_format)

## php-fpm 运行方式
[tv.php](./tv.php) 以及 [tptv.php](./tptv.php) 就是普通 `php-fpm` 模式下运行的文件，随便丢到 php 可运行的环境下就行，比如 `nginx + php`，请求参数跟上面一样 。

## 更新日志
```text
2024-10-20 23:50:01
    - 新增 tptv.php （对 tptv.m3u 的二次处理）

2024-10-19 17:50:33
    - 修改 t=1 text 输出格式 频道链接使用 # 参数聚合
    - 修改 m=1 聚合模式 频道链接携带 $备注 
    - 修改 m 默认值为 1=聚合

2024-10-19 10:19:32
    - 支持输出 m3u text 两种格式

2024-10-18 20:12:33
    - 央视频道 CGTN 同频道名合并

2024-10-18 16:47:47
    - 4K8K 保持原频道名，相同频道不进行合并。
    - 卫视频道 同频道名合并
```


### docker build
```text
// 多架构
docker buildx build --platform linux/amd64,linux/arm64,linux/arm/v7 -t yuexuangu/allinone_format:latest .
// 本机
docker build -t allinone_format .

// 推送
docker push yuexuangu/allinone_format:latest
```