#!/bin/bash

### ENV ###
CAKE_PATH='/app'
# Set PHP path
/app/src/Command/set_php_path.sh

### CAKE COMMANDS ###
bin/cake gen_search_list
