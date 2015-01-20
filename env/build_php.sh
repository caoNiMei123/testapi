#!/bin/bash
#此脚本用于静态页面文件整体送测打包

dirname=`pwd`

PHP_SRC=${dirname}/php
PHP_LIB=${dirname}/phplib
PHP_SO=${dirname}/phpso
PHP_LIB_PREFIX=/home/work/lib
PHP_PREFIX=/home/work/php
PHP_VERSION=5.4.24
#默认该目录下就一个php源码压缩包，请注意
PHP_SRC_FILE=${PHP_SRC}/php-${PHP_VERSION}.tar.gz


if [ ! -e $PHP_SRC_FILE ];then
	echo "php src source file not exist"
	exit;
fi

if [ -d $PHP_LIB_PREFIX ]; then
	rm -rf $PHP_LIB_PREFIX
fi
if [ -d $PHP_PREFIX ]; then
	rm -rf $PHP_PREFIX
fi

#copy lib files 
mkdir $PHP_LIB_PREFIX
cd ${PHP_LIB}/
tar xfz libmcrypt-2.5.8.tar.gz 
#libmcrypt
cd ${PHP_LIB}/libmcrypt-2.5.8/
chmod +x configure
chmod +x Makefile
chmod +x install-sh
./configure --prefix=${PHP_LIB_PREFIX}/libmcrypt
make && make install
#libcurl
cd ${PHP_LIB}/
tar xfz curl-7.30.0.tar.gz
cd ${PHP_LIB}/curl-7.30.0
chmod +x configure
chmod +x Makefile
chmod +x install-sh
./configure --prefix=${PHP_LIB_PREFIX}/curl
make && make install

#openssl
cd ${PHP_LIB}/
tar xfz openssl-1.0.1e.tar.gz
cd ${PHP_LIB}/openssl-1.0.1e
./config --prefix=${PHP_LIB_PREFIX}/openssl shared threads -fPIC
make && make install





cd $dirname
#copy php source file to output
mkdir -p output
cp ${PHP_SRC_FILE} output/

cd output 
tar xfz php-${PHP_VERSION}.tar.gz

#开始编译php
cd php-${PHP_VERSION}
conf="--prefix=${PHP_PREFIX} --with-config-file-path=${PHP_PREFIX}/etc --enable-pcntl --enable-sockets --with-zlib --enable-soap --with-mysql=mysqlnd --with-mysqli=mysqlnd --with-pdo-mysql=mysqlnd --with-iconv --enable-mbstring=utf8 --with-xmlrpc --enable-fpm --with-curl=${PHP_LIB_PREFIX}/curl --with-mcrypt=${PHP_LIB_PREFIX}/libmcrypt --with-openssl=${PHP_LIB_PREFIX}/openssl"
#echo $conf
#exit
./configure $conf
#exit;
make && make install
#拷贝编译好的so文件
mkdir -p ${PHP_PREFIX}/lib/php/extensions/no-debug-non-zts-20100525
cp ${PHP_SO}/* ${PHP_PREFIX}/lib/php/extensions/no-debug-non-zts-20100525/

#拷贝配置文件
rm -rf ${PHP_PREFIX}/etc
mkdir -p ${PHP_PREFIX}/bin
mkdir -p ${PHP_PREFIX}/log
mkdir -p ${PHP_PREFIX}/logs
cp -r ${PHP_SRC}/php/etc ${PHP_PREFIX}/
cp ${PHP_SRC}/php/bin/php-fpm_control* ${PHP_PREFIX}/bin/
chmod +x ${PHP_PREFIX}/bin/php-fpm_control
cd ${PHP_LIB_PREFIX}
echo "build succ"
