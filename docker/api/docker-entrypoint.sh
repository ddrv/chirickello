#!/bin/sh

set -e

#run, Forest, run!

/wait-for-it.sh auth:80 -t 0
/wait-for-it.sh task-tracker:80 -t 0
/wait-for-it.sh accounting:80 -t 0
/wait-for-it.sh analytics:80 -t 0

nginx -g 'daemon off;'
