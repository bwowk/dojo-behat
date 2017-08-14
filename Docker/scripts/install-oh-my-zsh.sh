#!/usr/bin/env bash
# Installs zsh and oh-my-zsh

# bash parameters
set -e  #   errexit  - Abort script at first error, when a command exits with non-zero status (except in until or while loops, if-tests, list constructs)
set -u  #   nounset  - Attempt to use undefined variable outputs error message, and forces an exit
set -x  #   xtrace   - Enable print commands and their arguments as they are executed.

# binaries
APT_GET=$(which apt-get)
CURL=$(which curl)

# define required packages
readonly PACKAGES=" \
            zsh \
            "
readonly USER_HOME="/home/behat"

# install required packages
"${APT_GET}" install \
              --no-install-recommends \
              --assume-yes \
              ${PACKAGES}

# download and run oh-my-zsh installation script
HOME=$USER_HOME sh -c "$(${CURL} -fsSL https://raw.githubusercontent.com/robbyrussell/oh-my-zsh/master/tools/install.sh)" || true

# replace configured zsh theme in .zshrc
ZSH_THEME=${ZSH_THEME:-gallois}
sed -i -e "s/robbyrussell/${ZSH_THEME}/g" "$USER_HOME/.zshrc"

# replace configured zsh theme in .zshrc
ZSH_PLUGINS=${ZSH_PLUGINS:-composer git}
sed -i -e "s/(git)/(${ZSH_PLUGINS})/g" "$USER_HOME/.zshrc"

# set histfile location
sed -i -e '/# User configuration/a HISTFILE="/code/.zsh_history"' "$USER_HOME/.zshrc"
