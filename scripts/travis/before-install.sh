#!/usr/bin/env bash

# Remove xdebug to make php execute faster.
phpenv config-rm xdebug.ini

# Add composer bin path.
export PATH="$HOME/.composer/vendor/bin:$PATH"
