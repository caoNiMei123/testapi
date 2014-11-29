<?php

$_CURRENT_DIR = dirname(__FILE__);
require_once($_CURRENT_DIR .'/ConnectionManInc.class.php');
require_once($_CURRENT_DIR .'/Connection.class.php');
require_once($_CURRENT_DIR .'/Strategy.class.php');
require_once($_CURRENT_DIR .'/ConnectionMysqli.class.php');
require_once($_CURRENT_DIR .'/ConnectionSocket.class.php');
require_once($_CURRENT_DIR .'/StrategySimple.class.php');
require_once($_CURRENT_DIR .'/StrategyState.class.php');

/**
 * 均衡冗余库，提供对外静态函数接口类
 */
class ConnectionMan
{
    private static $_connection = null;
    private static $_strategy = null;
    private static $_errno = 0;

    /**
     * 获得mysqli连接句柄
     *
     * @param [out] selServer   : 连接成功时，选中的机器信息
     * @param [in] arrServers   :  一组待选择的机器列表
     * @param [in] arrAuth   : 数据库授权信息
     * @param [in] intTimeout   : 超时时间，单位ms，最小为1秒
     * @param [in] arrStrategy   : 策略选择
     * @param [in] intBalanceCode   : 均衡因子，不使用时赋值为false
     * @return  object false: 失败；object: mysqli句柄，连接成功
     **/ 
    public static function getMysqli(&$selServer, $arrServers, $arrAuth, $intTimeout = 1000, 
		$arrStrategy = null, $intBalanceCode = false)
	{
		if (count($arrServers) == 0) {
			self::$_errno = ConnectionManInc::ERR_EMPTY_SERVER;
			return false;
		}
		$strConnectionType = ConnectionManInc::CONNECTION_TYPE_MYSQLI;
		$handle = self::getResource($selServer, $strConnectionType, $arrServers, $arrAuth,
			$intTimeout, $arrStrategy, $intBalanceCode);
		return $handle;
	}

	/**
	 * 获得socket连接句柄
	 *
	 * @param [out] selServer   : 连接成功时，选中的机器信息
	 * @param [in] arrServers   :  一组待选择的机器列表
	 * @param [in] intTimeout   : 超时时间，单位ms
	 * @param [in] arrStrategy   : 策略选择
	 * @param [in] intBalanceCode   : 均衡因子，不使用时赋值为false
	 * @return mixed false: 失败, object: socket句柄，连接成功
	 **/
	public static function getSocket(&$selServer, $arrServers, $intTimeout = 100,
		$arrStrategy = null, $intBalanceCode = false)
	{
		$arrAuth = array();
		if (count($arrServers) == 0) {
			self::$_errno = ConnectionManInc::ERR_EMPTY_SERVER;
			return false;
		}
		$strConnectionType = ConnectionManInc::CONNECTION_TYPE_SOCKET;
		$handle = self::getResource($selServer, $strConnectionType, $arrServers, $arrAuth,
			$intTimeout, $arrStrategy, $intBalanceCode);
		return $handle;
	}

    /**
     * 根据连接类型、均衡冗余策略，获取连接句柄
     *
     * @param [out] selServer   : 连接成功时，选中的机器信息
     * @param [in] strConnectionType   : 使用连接类的名称
     * @param [in] arrServers   :  一组待选择的机器列表
     * @param [in] arrAuth   : 授权信息, 有些连接时需要使用
     * @param [in] intTimeout   : 超时时间，单位ms
     * @param [in] arrStrategy   : 策略选择
     * @param [in] intBalanceCode   : 均衡因子，不使用时赋值为false
     * @return mixed false: 失败；object: 指定连接的句柄，连接成功
     **/ 
    public static function getResource(&$selServer, $strConnectionType, $arrServers,
    	$arrAuth, $intTimeout, $arrStrategy, $intBalanceCode)
    {
        $handle = false;
		$selServer = false;
		if (!is_array($arrStrategy) || !array_key_exists('name', $arrStrategy)) {
			$arrStrategy['name'] = '';
		}
		if (!is_array($arrStrategy) || !array_key_exists('config', $arrStrategy)) {
			$arrStrategy['config'] = array();
		}
		//获取策略对象
		self::$_strategy = self::_getStrategyInstance($arrStrategy['name'], $arrStrategy['config']);
		//获取连接对象
		self::$_connection = self::_getConnectionInstance($strConnectionType);
		
		if (!is_object(self::$_strategy)) {
			self::$_errno = ConnectionManInc::ERR_NO_STRATEGY;
			return false;
		}
		if (!is_object(self::$_connection)) {
			self::$_errno = ConnectionManInc::ERR_NO_CONNECTION;
			return false;
		}
		
		$intServerCount = count($arrServers);
		for ($i = 0; $i < $intServerCount; $i++) {
			$server = self::$_strategy->selectServer($arrServers, $intBalanceCode);
			if ($server == false) {
				//当前key，selectServer没有选出来，重选
				continue;
			}
			$handle = self::$_connection->connect($server, $intTimeout, $arrAuth);
			if ($handle === false) {
				//策略类标记连接失败，下次尝试其他服务器
				self::$_strategy->markFail($server);
			} else {
				//策略类标记连接成功，返回连接句柄
				self::$_strategy->markSucc($server);
				$selServer = $server;
				return $handle;
			}
		}
		self::$_errno = ConnectionManInc::ERR_ALL_FAILED;
		return false;
    }

    /**
     * 根据连接类型new一个连接类对象，负责实际连接
     *
     * @param [in] strConnectionType   : 使用连接类的名称
     * @return mixed false: 失败；class: 连接类对象
     **/ 
    private static function _getConnectionInstance($strConnectionType)
    {
        $strConnectionClass = (ConnectionManInc::CONNECTION_CLASS_PREFIX).$strConnectionType;
		if (class_exists($strConnectionClass)) {
			$objConn = new $strConnectionClass();
			return $objConn;
		}
        return false;
    }

    /**
     * 根据策略选择，new一个策略类对象，负责机器选择。
     *
     * @param [in] strStrategyName   : 使用策略类的名称
     * @param [in] arrConfig   : 自定义配置参数
     * @return mixed false: 失败；class: 连接类对象
     **/ 
    private static function _getStrategyInstance($strStrategyName, $arrConfig)
    {
		if (empty($strStrategyName)) {
			$strStrategyName = ConnectionManInc::DEFAULT_STRATEGY;
		}
		$strStrategyClass = (ConnectionManInc::STRATEGY_CLASS_PREFIX) . $strStrategyName;
		if (class_exists($strStrategyClass)) {
			$objStrategy = new $strStrategyClass($arrConfig);
			return $objStrategy;
		}
        return false;
    }

    /**
     * 手工设置某个机器一次连接失败，在连接成功，但是读写失败时，可以使用。
     *
     * @param [in] server   : 机器信息
     * @return  bool false: 失败；true: 成功
     **/ 
    public static function setUnavailable($server)
    {
        return self::$_strategy->markFail($server);
    }

    /**
     * 获取最后一次错误号
     *
     * @return  int
     **/ 
    public static function getLastErrno()
    {
        return self::$_errno;
    }
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */