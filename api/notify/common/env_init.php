<?php

define('IS_DEBUG', false);
define('APP_NAME' , 'notify');
define('APP_PATH', dirname(__FILE__) .'/..');
define('APP_CONF_PATH', APP_PATH .'/../conf/' . APP_NAME);
define('APP_PARENT_PATH', dirname(__FILE__) .'/../..');
define('APP_CONF_PARENT_PATH', APP_PATH .'/../conf');

require_once(APP_PATH .'/../conf/phplib/Public.conf.php');
require_once(APP_PATH .'/../common/commonDao.inc.php');

// We will use autoloader instead of include path.
$appIncludePath = APP_PATH .'/model/:' .
				  APP_PATH .'/scripts/:' .
                  APP_PATH .'/common/:' .
				  APP_CONF_PATH .'/:';
				  
ini_set('include_path', ini_get('include_path') . ':' . $appIncludePath);
require_once('WorkerConfig.class.php');
require_once('NotifyConfig.class.php');

date_default_timezone_set("Asia/Shanghai");
//error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);
//ini_set('display_errors', 1);

$GLOBALS['LOG'] = array(
	'type'		=> LOG_TYPE,
	'level'		=> LOG_LEVEL,
	'path'		=> (LOG_TYPE == 'LOCAL_LOG') ? APP_PATH .'/../log' : 'log',
	'filename'	=> 'notify.log',
	'stats'		=> array(
    'demo'		=> 'notify.stat.log',
	),
);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
