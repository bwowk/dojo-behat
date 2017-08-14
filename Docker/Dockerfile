#
# Remember to bump up the behat image version on docker-compose.yml if changes were
# made on the Dockerfile, to prevent cache issues.
# i.e. change from "image: behat:1.0" to "image: behat:1.1"
#
FROM composer/composer:1.2
MAINTAINER CI&T Automation Squad <@bwowk, @aboscatto>

# define Docker image label information
LABEL com.ciandt.vendor="CI&T Software SA" \
      com.ciandt.release-date="2016-11-24" \
      com.ciandt.maintainers.1="André Boscatto - @aboscatto" \
      com.ciandt.maintainers.2="Bruno Wowk - @bwowk"

# defines root user, to perform privileged operations
USER root

# Setup Behat volume and workdir
VOLUME ["/code"]
WORKDIR /code

# update apt cache before running scripts
# and install some utils
RUN apt-get update \
  && apt-get install \
  nano \
  sudo

#create behat group and user and give it sudo permissions
RUN groupadd -g 1000 behat \
&& useradd --create-home -u 1000 -g behat behat \
&& sed -i -e '/# User privilege specification/a behat	ALL=(ALL:ALL) NOPASSWD: ALL' "/etc/sudoers"

# copy scripts and give them running permissions
COPY scripts /scripts
RUN chgrp -R behat /scripts
RUN chmod -R 777 /scripts

# run helper scripts
RUN cd /scripts \
&& ./install-oh-my-zsh.sh \
&& ./install-php-extensions.sh


#clear apt cache after installing everything
RUN apt-get clean \
&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

USER behat

# Add composer bin folder to the PATH
ENV PATH=$PATH:/code/bin

# docker entrypoint/cmd configuration
# remove parent image (composer) entrypoint
ENTRYPOINT []
CMD ["/bin/zsh"]