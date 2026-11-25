ARG ALPINE_VERSION=3.23
FROM alpine:${ALPINE_VERSION}

RUN apk add --no-cache pgbouncer ca-certificates \
    && (getent group pgbouncer >/dev/null || addgroup -S pgbouncer) \
    && (getent passwd pgbouncer >/dev/null || adduser -S -G pgbouncer -H pgbouncer) \
    && mkdir -p /etc/pgbouncer /var/run/pgbouncer \
    && chown -R pgbouncer:pgbouncer /etc/pgbouncer /var/run/pgbouncer

COPY pgbouncer.ini /etc/pgbouncer/pgbouncer.ini
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && chown pgbouncer:pgbouncer /etc/pgbouncer/pgbouncer.ini

USER pgbouncer
EXPOSE 6432
ENTRYPOINT ["/entrypoint.sh"]
