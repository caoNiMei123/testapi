<?php

/**
 * 针对页面异步的action
 **/
class AsyncBaseAction extends LogicBaseAction
{
	public function initial($initObject)
	{
		parent::initial($initObject);
		
	    // 判断用户登录结果
		if (isset($this->action_params['checkLogin']) && 
			true == $this->action_params['checkLogin']) {
			if (CommonConfig::$checkLoginResult['succ'] != $this->user_info['result']) {
				if (CommonConfig::$checkLoginResult['login'] == $this->user_info['result']) {
					throw New Exception("Common.auth user is not login");
				} elseif (CommonConfig::$checkLoginResult['err'] == $this->user_info['result']) {
					throw New Exception("Common.internal server backend error");
				}
			}
		}
		
		return true;
	}
	
	protected function _errorHandler($errno, $errstr, $errfile, $errline) {
		if (!($errno & error_reporting())) {
			return false;
		} elseif ($errno === E_USER_NOTICE) {
			CLog::trace('caught trace, error_code:%d,error_msg:%s,file:%s,line:%d', $errno, $errstr, $errfile, $errline);
			return false;
		} elseif ($errno === E_STRICT) {
			return false;
		} else {
			restore_error_handler();
			CLog::fatal('caught error, error_code:%d,error_msg:%s,file:%s,line:%d', $errno, $errstr, $errfile, $errline);
			return true;
		}
	}
	
	public function errorHandler()
	{
		$error = func_get_args();
		if (false === $this->_errorHandler($error[0], $error[1], $error[2], $error[3])) { 
			return;
		}
		if ((defined('IS_DEBUG') && IS_DEBUG)) {
			unset($error[4]);
			echo "<pre>\n";
			print_r($error);
			echo "\n</pre>";
		}
		
		header('HTTP/1.1 200');
		$this->set("st", -1);
		$this->set("reqid", $this->logid);
		$this->set("msg", CommonConst::$errorDescs[CommonConst::EC_CM_SERVICE_INVALID]);
		CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', CommonConst::EC_CM_SERVICE_INVALID);
		$this->outputResponse();
		exit();
	}

	protected function _exceptionHandler($ex)
	{
		restore_exception_handler();
		$errcode = $ex->getMessage();
		$action = NULL;
    	if (0 < ($pos = strpos($errcode,' '))) {
			$errcode = substr($errcode, 0, $pos);
			$expVal = explode('.', $errcode);
			if ('false' != $expVal && 0 != count($expVal))
			{
			    $action = $expVal[0];
			}
		}
		
		// 判断配置类是否存在，若存在则返回已定义的错误码，否则返回通用错误码
		if (!class_exists($action . 'Config')) {
			$action = 'Common';
		}
		
		$config = $action . 'Config';
        
        $ReflectionClass = new ReflectionClass($config);
        $arrExceptionReturnMap = $ReflectionClass->getStaticPropertyValue('arrExceptionReturnMap');
        	
	    if(!array_key_exists($errcode, $arrExceptionReturnMap)) {
			$errcode = $action . '.internal';
		}
	
		$httpCode = $arrExceptionReturnMap[$errcode]['httpCode'];
		header("HTTP/1.1 $httpCode");
		$this->set("st", -1);
		$this->set("reqid", $this->logid);
		$this->set("msg", CommonConst::$errorDescs[$arrExceptionReturnMap[$errcode]['errno']]);
		CLog::warning('Caught exception, error_code:%s, trace:%s', $errcode, $ex->__toString());
		CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', $arrExceptionReturnMap[$errcode]['errno']);
	}

	public function exceptionHandler($ex)
	{
		$this->_exceptionHandler($ex);
		$this->outputResponse();
		exit();
	}
	
	public function responseError($msg)
	{
		header('HTTP/1.1 200');
		$this->set('st', -1);
		$this->set('data', '');
		$this->set('msg', $msg);
		
		// 打印调用者信息
		$debugInfo = debug_backtrace();
		$file = (isset($debugInfo[0]['file'])) ? $debugInfo[0]['file'] : '';
		$line = (isset($debugInfo[0]['line'])) ? $debugInfo[0]['line'] : '';
		CLog::warning("Response error, path:$file, line:$line, msg:$msg");
		
		$this->outputResponse();
		CLog::notice("MVC-FRAMEWORK execute complete, time cost[".bdTimer::toString() . "] ret_code=[0] request done");
		exit();
	}

	public function responseOk($data, $msg)
	{
		header('HTTP/1.1 200');
		$this->set('st', 0);
		$this->set('data', $data);
		$this->set('msg', $msg);
		
		// 打印调用者信息
		$debugInfo = debug_backtrace();
		$file = (isset($debugInfo[0]['file'])) ? $debugInfo[0]['file'] : '';
		$line = (isset($debugInfo[0]['line'])) ? $debugInfo[0]['line'] : '';
		CLog::trace("Response OK, path:$file, line:$line, msg:$msg");
		
		$this->outputResponse();
		CLog::notice("MVC-FRAMEWORK execute complete, time cost[".bdTimer::toString() . "] ret_code=[0] request done");
		exit();
	}
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
