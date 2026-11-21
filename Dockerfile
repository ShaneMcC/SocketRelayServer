FROM shanemcc/docker-cli-php-base:latest8
MAINTAINER Shane Mc Cormack <dataforce@dataforce.org.uk>

COPY . /socketRelayServer

WORKDIR /socketRelayServer

RUN \
  useradd socketrelayserver && \
  mkdir -p /home/socketrelayserver/ && \
  chown -Rfv socketrelayserver: /socketRelayServer /home/socketrelayserver && \
  su socketrelayserver --shell=/bin/bash -c "cd /socketRelayServer; /usr/bin/composer install" && \
  chmod a+x /socketRelayServer/run.php

EXPOSE 3302

VOLUME ["/data"]

ENV FAILFILE /data/.failedMessages
ENV LOCALCONF /data/config.local.php

CMD ["/socketRelayServer/run.php"]
