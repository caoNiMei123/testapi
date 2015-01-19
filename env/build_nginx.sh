#!/bin/sh
TYPE='luajit'
SRC_PATH=$(pwd)
cd	$SRC_PATH 
#NGX_HOME="/home/work/local/nginx"
NGX_HOME="$SRC_PATH/tmp/nginx"
NGX_SBIN_PATH="$NGX_HOME/sbin/nginx"
NGX_CONF_PATH="$NGX_HOME/conf/nginx.conf"  
NGX_ERROR_LOG_PATH="$NGX_HOME/logs/error_log"  
NGX_HTTP_ACCESS_LOG_PATH="$NGX_HOME/logs/access_log"
NGX_PIDFILE_PATH="$NGX_HOME/run/nginx.pid"  
NGX_WORKER_PROC_USER=work  
NGX_WORKER_PROC_GROUP=work    
MOD_SRC_PATH=$SRC_PATH/tmp
mkdir tmp
cp $SRC_PATH/nginx/*.tar.gz ./tmp/
cp -r $SRC_PATH/nginx/nginx_http_vcode_module ./tmp/
cd tmp
tar -xvzf ./pcre-8.33.tar.gz 
tar -xvzf ./openssl-1.0.1j.tar.gz
tar -xvzf ./echo-nginx-module-0.46.tar.gz 
tar -xvzf ./headers-more-nginx-module-0.22.tar.gz 
tar -xvzf ./ngx_devel_kit-0.2.18.tar.gz
tar -xvzf ./nginx_http_vcode_module.tar.gz


PCRE_SRC=$SRC_PATH/tmp/pcre-8.33/
OPENSSL_SRC=$SRC_PATH/tmp/openssl-1.0.1j/ 
OPTS_WITH="--with-ipv6 \
        --with-http_ssl_module \
        --with-openssl=$OPENSSL_SRC \
        --with-http_realip_module \
        --with-http_addition_module \
        --with-http_sub_module \
        --with-http_gunzip_module \
        --with-http_gzip_static_module \
        --with-http_stub_status_module \
        --with-pcre=$PCRE_SRC \
        --with-pcre-jit \
        --with-cc-opt=-Wno-error \
        --with-ld-opt=-lstdc++"

#for options of --without-* of configuration script
OPTS_WITHOUT="--without-http_userid_module \
        --without-mail_pop3_module \
        --without-mail_imap_module \
        --without-mail_smtp_module"


HEADERS_MORE_MOD_SRC="$MOD_SRC_PATH/headers-more-nginx-module-0.22"
NDK_MOD_SRC="$MOD_SRC_PATH/ngx_devel_kit-0.2.18"
ECHO_MOD_SRC="$MOD_SRC_PATH/echo-nginx-module-0.46"
VCODE_MOD_SRC="$MOD_SRC_PATH/nginx_http_vcode_module" 


#front modules
# 2014-12-08: add lua-module, bns-module and lua_upstream-module to front ngx by linxg
OPTS_ADD_MODULE="--add-module=$HEADERS_MORE_MOD_SRC \
        --add-module=$ECHO_MOD_SRC        \
        --add-module=$VCODE_MOD_SRC \
        --add-module=$NDK_MOD_SRC \
        --add-module=$ECHO_MOD_SRC"

config_opts="--prefix=$NGX_HOME\
        $OPTS_WITH \
        $OPTS_WITHOUT \
        $OPTS_ADD_MODULE" 

cd  $SRC_PATH/nginx/ 
tar -xvzf nginx-1.4.2.tar.gz
cd  nginx-1.4.2/
echo "./configure $config_opts"
./configure $config_opts
make 
#make install
cd	$SRC_PATH
mkdir -p ./output/nginx/bin ./output/nginx/conf ./output/nginx/lib ./output/nginx/logs


cp nginx/nginx-1.4.2/objs/nginx output/nginx/bin/
cp nginx.conf ./output/nginx/conf/ 
cp nginx/reload.sh ./output/nginx/reload.sh
cp nginx/restart.sh ./output/nginx/restart.sh

#rm ./tmp -rf
exit 0
