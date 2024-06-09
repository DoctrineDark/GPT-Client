FROM ubuntu:20.04 AS redisjson

RUN apt-get update
RUN apt-get upgrade -y git
RUN apt-get install -y curl gcc
RUN apt-get install -y libclang-dev

WORKDIR /
RUN curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs -o install-rust.sh && sh ./install-rust.sh -y
RUN git clone https://github.com/RedisJSON/RedisJSON.git --branch v2.4.4
WORKDIR /RedisJSON
RUN /root/.cargo/bin/cargo build --release


FROM ubuntu:20.04 AS redisearch
RUN apt-get update
RUN apt-get upgrade -y git
RUN apt-get install -y python3 python3-pip
WORKDIR /
RUN git clone https://github.com/RediSearch/RediSearch.git --branch v2.6.5 --recursive
WORKDIR /RediSearch
RUN ./sbin/setup && make

CMD ["/bin/bash"]

# Largely copy/pasted from https://github.com/Snapchat/KeyDB/blob/a1978ce04c6523871f4a20a2faa739d27735da63/pkg/docker/Dockerfile
FROM ubuntu:20.04
SHELL ["/bin/bash","-c"]
RUN groupadd -r keydb && useradd -r -g keydb keydb
# use gosu for easy step-down from root: https://github.com/tianon/gosu/releases
ENV GOSU_VERSION 1.14
RUN set -eux; \
        savedAptMark="$(apt-mark showmanual)"; \
        apt-get update; \
        apt-get install -y --no-install-recommends ca-certificates dirmngr gnupg wget; \
        rm -rf /var/lib/apt/lists/*; \
        dpkgArch="$(dpkg --print-architecture | awk -F- '{ print $NF }')"; \
        wget -O /usr/local/bin/gosu "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch"; \
        wget -O /usr/local/bin/gosu.asc "https://github.com/tianon/gosu/releases/download/$GOSU_VERSION/gosu-$dpkgArch.asc"; \
        export GNUPGHOME="$(mktemp -d)"; \
        gpg --batch --keyserver hkps://keys.openpgp.org --recv-keys B42F6819007F00F88E364FD4036A9C25BF357DD4; \
        gpg --batch --verify /usr/local/bin/gosu.asc /usr/local/bin/gosu; \
        gpgconf --kill all; \
        rm -rf "$GNUPGHOME" /usr/local/bin/gosu.asc; \
        apt-mark auto '.*' > /dev/null; \
        [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark > /dev/null; \
        apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
        chmod +x /usr/local/bin/gosu; \
        gosu --version; \
        gosu nobody true
# build KeyDB
ARG BRANCH
RUN set -eux; \
        \
        savedAptMark="$(apt-mark showmanual)"; \
        apt-get update; \
        DEBIAN_FRONTEND=noninteractive apt-get install -qqy --no-install-recommends \
                dpkg-dev \
                pkg-config \
                ca-certificates \
                build-essential \
                nasm \
                autotools-dev \
                autoconf \
                libjemalloc-dev \
                tcl \
                tcl-dev \
                uuid-dev \
                libcurl4-openssl-dev \
                libbz2-dev \
                libzstd-dev \
                liblz4-dev \
                libsnappy-dev \
                libssl-dev \
                git; \
        cd /tmp && git clone --branch v6.3.2 https://github.com/Snapchat/KeyDB.git --recursive; \
        cd /tmp/KeyDB; \
        # disable protected mode as it relates to docker
        grep -E '^ *createBoolConfig[(]"protected-mode",.*, *1 *,.*[)],$' ./src/config.cpp; \
        sed -ri 's!^( *createBoolConfig[(]"protected-mode",.*, *)1( *,.*[)],)$!\10\2!' ./src/config.cpp; \
        grep -E '^ *createBoolConfig[(]"protected-mode",.*, *0 *,.*[)],$' ./src/config.cpp; \
        make -j$(nproc) BUILD_TLS=yes ENABLE_FLASH=yes; \
        cd src; \
        strip keydb-cli keydb-benchmark keydb-check-rdb keydb-check-aof keydb-diagnostic-tool keydb-sentinel keydb-server; \
        mv keydb-server keydb-cli keydb-benchmark keydb-check-rdb keydb-check-aof keydb-diagnostic-tool keydb-sentinel /usr/local/bin/; \
        # clean up unused dependencies
        echo $savedAptMark; \
        apt-mark auto '.*' > /dev/null; \
        [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark > /dev/null; \
        find /usr/local -type f -executable -exec ldd '{}' ';' \
               | awk '/=>/ { print $(NF-1) }' \
               | sed 's:.*/::' \
               | sort -u \
               | xargs -r dpkg-query --search \
               | cut -d: -f1 \
               | sort -u \
               | xargs -r apt-mark manual \
        ; \
        apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
        rm -rf /var/lib/apt/lists/*; \
# create working directories and organize files
RUN \
        mkdir /data && chown keydb:keydb /data; \
        mkdir /flash && chown keydb:keydb /flash; \
        mkdir -p /etc/keydb; \
        cp /tmp/KeyDB/keydb.conf /etc/keydb/; \
        sed -i 's/^\(daemonize .*\)$/# \1/' /etc/keydb/keydb.conf; \
        sed -i 's/^\(dir .*\)$/# \1\ndir \/data/' /etc/keydb/keydb.conf; \
        sed -i 's/^\(logfile .*\)$/# \1/' /etc/keydb/keydb.conf; \
        sed -i 's/protected-mode yes/protected-mode no/g' /etc/keydb/keydb.conf; \
        sed -i 's/^\(bind .*\)$/# \1/' /etc/keydb/keydb.conf; \
        ln -s keydb-cli redis-cli; \
        cd /etc/keydb; \
        ln -s keydb.conf redis.conf; \
        rm -rf /tmp/*
# generate entrypoint script
RUN set -eux; \
        echo '#!/bin/sh' > /usr/local/bin/docker-entrypoint.sh; \
        echo 'set -e' >> /usr/local/bin/docker-entrypoint.sh; \
        echo "# first arg is '-f' or '--some-option'" >> /usr/local/bin/docker-entrypoint.sh; \
        echo "# or first arg is `something.conf`" >> /usr/local/bin/docker-entrypoint.sh; \
        echo 'if [ "${1#-}" != "$1" ] || [ "${1%.conf}" != "$1" ]; then' >> /usr/local/bin/docker-entrypoint.sh; \
        echo '        set -- keydb-server "$@"' >> /usr/local/bin/docker-entrypoint.sh; \
        echo 'fi' >> /usr/local/bin/docker-entrypoint.sh; \
        echo "# allow the container to be started with `--user`" >> /usr/local/bin/docker-entrypoint.sh; \
        echo 'if [ "$1" = "keydb-server" -a "$(id -u)" = "0" ]; then' >> /usr/local/bin/docker-entrypoint.sh; \
        echo "        find . \! -user keydb -exec chown keydb '{}' +" >> /usr/local/bin/docker-entrypoint.sh; \
        echo '        exec gosu keydb "$0" "$@"' >> /usr/local/bin/docker-entrypoint.sh; \
        echo 'fi' >> /usr/local/bin/docker-entrypoint.sh; \
        echo 'exec "$@"' >> /usr/local/bin/docker-entrypoint.sh; \
        chmod +x /usr/local/bin/docker-entrypoint.sh
# set remaining image properties
VOLUME /data
WORKDIR /data
ENV KEYDB_PRO_DIRECTORY=/usr/local/bin/
ENTRYPOINT ["docker-entrypoint.sh"]
EXPOSE 6379

# Import the modules for RediSearch and RedisJSON
COPY --from=redisearch /RediSearch/bin/linux-x64-release/search/redisearch.so /opt/lib/
COPY --from=redisjson  /RedisJSON/target/release/librejson.so /opt/lib/

CMD ["keydb-server", "/etc/keydb/keydb.conf", "--loadmodule", "/opt/lib/librejson.so", "--loadmodule", "/opt/lib/redisearch.so"]