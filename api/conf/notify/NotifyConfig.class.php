<?php


class NotifyConfig
{	
	const DRIVER_LIMIT = 50;
	
	public static $notifyTimeout = 300; // 单位: 秒
	
    // 单位：公里
    public static $NotifyRectangleRange = 7;
    
    // 单位：公里
    public static $NotifyBetweenDistance = 4;
    
    // 需要进行筛选的司机数量下限
    public static $driverNumLowerBound = 10;
    
    public static $arrNotifyTaskStatus = array(
        'new'   => 0,
    	'processing' => 1,
    	'succ'	=> 2,
    	'cancel' => 3,
    	'error_invalid_pid' => 4,
    	'error_no_driver' => 5,
    	'error_push' => 6,
    );
    
    public static $arrPushType  = array(
        'create_order' => 1,
        'cancel_order' => 2,
        'accept_order' => 3,
        'driver_location' => 4,
        'order_info' => 5,
    );
    
	// 数据库server配置
	public static $DBServer = array(
		'host' => array(
			array('rdsv63ujvaq3yma.mysql.rds.aliyuncs.com', 3306),
			array('rdsv63ujvaq3yma.mysql.rds.aliyuncs.com', 3306), 
		),
		
		'uname'    => 'pinche',
		'password' => 'pinche',
		'dbname'   => 'carpooldb',
		'charset'  => 'utf8',
	);
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
