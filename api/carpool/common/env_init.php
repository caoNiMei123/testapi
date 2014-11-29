<?php

define('IS_DEBUG', false);
define('APP_NAME' , 'carpool');
define('APP_PATH', dirname(__FILE__) .'/..');
define('APP_CONF_PATH', APP_PATH .'/../conf/' . APP_NAME);
define('APP_PARENT_PATH', dirname(__FILE__) .'/../..');
define('APP_CONF_PARENT_PATH', APP_PATH .'/../conf');

require_once(APP_PATH .'/../conf/phplib/Public.conf.php');
require_once(APP_PATH .'/../common/commonDao.inc.php');
require_once(APP_PATH .'/controller/uri_dispatch_rules.php');

// We will use autoloader instead of include path.
$appIncludePath = APP_PATH .'/action/:'.
				  APP_PATH .'/model/:' .
                  APP_PATH .'/common/:' .
				  APP_CONF_PATH .'/:';
				  
ini_set('include_path', ini_get('include_path') . ':' . $appIncludePath);
require_once ('CarpoolConfig.class.php');

date_default_timezone_set("Asia/Shanghai");
//error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$GLOBALS['LOG'] = array(
	'type'		=> LOG_TYPE,
	'level'		=> LOG_LEVEL,
	'path'		=> (LOG_TYPE == 'LOCAL_LOG') ? APP_PATH .'/../log' : 'log',
	'filename'	=> 'carpool.log',
	'stats'		=> array(
    'demo' => 'carpool.stat.log',
	),
);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
