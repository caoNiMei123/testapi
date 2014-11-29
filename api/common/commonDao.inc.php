<?php

/**
 * Register all classes into phplib's autoloader
 */
define('CARPOOL_COMMON_PATH', dirname(__FILE__));
define('CARPOOL_COMMON_ACTION_PATH', CARPOOL_COMMON_PATH .'/action');
define('CARPOOL_COMMON_MODEL_PATH', CARPOOL_COMMON_PATH .'/model');
define('CARPOOL_COMMON_CONF_PATH', CARPOOL_COMMON_PATH .'/../conf/common');
define('CARPOOL_COMMON_ERRCODE_PATH', CARPOOL_COMMON_PATH .'/errCode');
ini_set('include_path', ini_get('include_path') . ':' . CARPOOL_COMMON_MODEL_PATH);

$g_arrCommonClasses = array(
	'BaseAction'			=> CARPOOL_COMMON_ACTION_PATH .'/BaseAction.class.php',
	'TemplateBasedAction'	=> CARPOOL_COMMON_ACTION_PATH .'/TemplateBasedAction.class.php',
	'PageBuilderAction'		=> CARPOOL_COMMON_ACTION_PATH .'/PageBuilderAction.class.php',
	'CommonWorkflowAction'	=> CARPOOL_COMMON_ACTION_PATH .'/CommonWorkflowAction.class.php',
	'HashBaseAction'	    => CARPOOL_COMMON_ACTION_PATH .'/HashBaseAction.class.php',
	'OpenapiBaseAction'		=> CARPOOL_COMMON_ACTION_PATH .'/OpenapiBaseAction.class.php',
	'AsyncBaseAction'		=> CARPOOL_COMMON_ACTION_PATH .'/AsyncBaseAction.class.php',
	'LogicBaseAction'		=> CARPOOL_COMMON_ACTION_PATH .'/LogicBaseAction.class.php',
	
	'CommonConfig'			=> CARPOOL_COMMON_CONF_PATH .'/CommonConfig.class.php',
	'CommonConst'			=> CARPOOL_COMMON_CONF_PATH .'/CommonConst.class.php',
	'AuthorizationConfig'	=> CARPOOL_COMMON_CONF_PATH .'/AuthorizationConfig.class.php',
	'AuthorizationManager'	=> CARPOOL_COMMON_MODEL_PATH .'/AuthorizationManager.class.php',
	'TplConfig'			    => CARPOOL_COMMON_CONF_PATH .'/TplConfig.class.php',

);

RegisterMyClasses($g_arrCommonClasses);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
