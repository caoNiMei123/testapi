<?php

/**
 * mysqli连接类,需要实现connect方法。
 */
class ConnectionMysqli extends Connection
{
    /**
     * 实现Connection类的连接方法，进行mysqli实际连接。
     *
     * @param [in] server   : 被连接机器信息
     * @param [in] intTimeout   : 超时时间，单位ms, 最小为1秒。
     * @param [in] arrAuth   :  某些连接可能用到授权信息
     * @return
     **/ 
    function connect($server, $intTimeout, $arrAuth)
    {
        $host = $server['host'];
        $port = $server['port'];
        $dbname = $arrAuth['dbname'];
        $dbuser = $arrAuth['dbuser'];
        $dbpass = $arrAuth['dbpass'];

        $mysqli = mysqli_init();
        $intTimeoutSec = $intTimeout > 1000 ? $intTimeout/1000 : 1;
		$bolRet = mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, $intTimeoutSec);
		if (!$bolRet) {
			return false;
		}
		$bolRet = @mysqli_real_connect($mysqli, $host, $dbuser, $dbpass, $dbname, $port);
		if (!$bolRet) {
			//连接失败
			$errno = mysqli_connect_errno();
			return false;
		}
        return $mysqli;
    }
}


/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */