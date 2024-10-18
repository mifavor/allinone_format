## 功能简介：
本项目是对 https://hub.docker.com/r/youshandefeiyang/allinone 项目返回 tv.m3u 内容进行二次频道分组&频道名格式化。所以安装本项目前，请先安装 youshandefeiyang/allinone 项目。

## docker 运行方式（推荐）：
```text
docker run -d --restart=always -p 35456:35456 --name allinone_format yuexuangu/allinone_format:latest
```

## 请求参数
```text
http://内网IP:35456/tv.php?h=allinoneIP&p=allinoneHost&m=0

请求参数说明：
h  可选参数  allinone 项目部署的内外网 IP或域名（不能使用 127.0.0.1），默认值 = 请求的内网IP
p  可选参数  allinone 项目部署的端口, 默认值 = 35455
m  可选参数  是否对频道连接进行聚合，默认=0 不聚合（推荐天光云影使用），1=聚合（推荐tvbox类使用）

请求例子：
http://192.168.31.50:35456/tv.php
上面请求等同于
http://192.168.31.50:35456/tv.php?h=192.168.31.50&p=35455&m=0
```

## php-fpm 运行方式
[./script/tv.php](./scripts/tv.php) 就是个普通 `php-fpm` 模式下运行的文件，随便丢到 php 可运行的环境下就行，比如 `nginx + php`，请求参数跟上面一样 。

## 更新日志
```text
2024-10-18 20:12:33
    - 央视频道 CGTN 同频道名合并

2024-10-18 16:47:47
    - 4K8K 保持原频道名，相同频道不进行合并。
    - 卫视频道 同频道名合并
```
