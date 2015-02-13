<?php

class IPCConfig
{
    public static $domain_info = array(
        'machine' => '/tmp/tmp_socket',
        'connect_timeout' => 1000,
        'timeout' => 10000,
    	'receive_timeout' => 10000, // socket接收数据超时，单位: 微秒
    );
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
