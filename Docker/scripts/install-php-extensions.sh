#!/usr/bin/env bash
# Installs PHP Extensions

# bash parameters
set -e  #   errexit  - Abort script at first error, when a command exits with non-zero status (except in until or while loops, if-tests, list constructs)
set -u  #   nounset  - Attempt to use undefined variable outputs error message, and forces an exit
set -x  #   xtrace   - Enable print commands and their arguments as they are executed.

# binaries
APT_GET=$(which apt-get)

# define required packages
readonly PACKAGES=" \
            libc-client-dev \
            libkrb5-dev \
            libmagickwand-dev \
            "

# install required packages
"${APT_GET}" install \
              --no-install-recommends \
              --assume-yes \
              ${PACKAGES}

# install php PECL extensions
pecl install imagick
docker-php-ext-enable imagick

# install php extensions using helper scripts
docker-php-ext-configure imap --with-kerberos --with-imap-ssl
docker-php-ext-install imap xml
