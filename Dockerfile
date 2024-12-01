FROM alpine:latest

# 安装 PHP 和必要扩展
RUN apk add --no-cache php php-curl php-openssl

# 设置工作目录
WORKDIR /app

# 复制服务器代码 (合并COPY指令减少层数)
COPY server/ deprecated/*.php ./

# /app/config/ 需要从外部挂载, 并且需要读取 & 写入权限
VOLUME /app/config/

# 暴露端口
EXPOSE 35456

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s \
    CMD wget --no-verbose --tries=1 --spider http://127.0.0.1:35456/favicon.ico || exit 1

# 启动命令
CMD ["php", "-S", "0.0.0.0:35456", "index.php"] 