<?php


class NotifyConfig
{
	// 最多处理的task个数，超过这个个数，该进程就退出
	const MAX_TASK_PROCESS_NUM = 10000;
	
	// 没有任务时的休眠时间，单位: 毫秒
	const WORKER_SLEEP_TIME = 10000;
	
	const DRIVER_LIMIT = 50;
	
	public static $notifyTimeout = 1; // 单位: 秒
	
	public static $notifyMsgTimeout = 100; // 单位：秒
	
	public static $arProcessStatus = array(
        'no_task'   => 0,
		'no_driver' => 1,
    );
    
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
