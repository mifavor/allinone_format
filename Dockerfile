FROM alpine:latest

RUN apk add --no-cache php

COPY scripts/ /opt/

EXPOSE 35456

ENTRYPOINT ["/bin/sh", "/opt/start.sh"]
