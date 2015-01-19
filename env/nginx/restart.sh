#!/bin/bash
killall nginx 
./bin/nginx  -p /home/work/local/nginx/ -c /home/work/local/nginx/conf/nginx.conf