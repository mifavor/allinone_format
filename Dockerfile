FROM alpine:latest

RUN apk add --no-cache php

COPY tv.php tptv.php migu.php /opt/

EXPOSE 35456

ENTRYPOINT ["php", "-S", "0.0.0.0:35456", "-t", "/opt/"]
