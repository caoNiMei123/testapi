<?php

class BaseAction extends Action
{
	/**
	 * Property key name for context data.
	 * 
	 * @var string
	 */
	const CONTEXT_PARAMS = 'context_params';
	
	/**
	 * Action data, which is only used in current Action instance.
	 * 
	 * @var mixed
	 */
	protected $action_params;
	
	/**
	 * Context data passed between Actions, only available when execute() method be called.
	 * 
	 * @var array
	 */
	protected $context_params;
	
	/**
	 * Charset of http response for current request.
	 * 
	 * @var string
	 */
	protected $charset = 'utf-8';

	/**
     * array_merge($_GET, $_POST)
	 *  
	 * @var array
	 */	
	protected $requests = array();

	/**
     * 存储当前登录用户的信息
	 *  
	 * @var array
	 */	
	protected $user_info = array();
	
	/**
	 * Whether is a POST request
	 * 
	 * @var bool
	 */
	protected $is_post = false;
	
	/**
	 * Whether we are under debug mode or not.
	 * 
	 * @var bool
	 */
	protected $is_debug = false;

	/**
	 * Initialize current action
	 * 
	 * @param mix $initObject Params for current action
	 * @return bool
	 */
	public function initial($initObject)
	{
        set_error_handler(array($this,'errorHandler'));
		set_exception_handler(array($this,'exceptionHandler'));
		
		$this->action_params = $initObject;
		
		if (empty($this->action_params['charset'])) {
			$this->charset = defined('DEFAULT_CHARSET') ? DEFAULT_CHARSET : 'utf-8';
		} else {
			$this->charset = $this->action_params['charset'];
		}
		
		if (defined('IS_DEBUG') && IS_DEBUG && isset($_GET['test']) && intval($_GET['test']) === 1) {
			$this->is_debug = true;
		}
		
		$this->is_post = (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') === 0);
		
		$this->requests = array_merge($_GET, $_POST);
		
        if (isset($this->requests['request_id']))
        {
            $this->logid = $this->requests['request_id'];
            CLog::setLogId($this->requests['request_id']);
        }
        else
        {
            $this->logid = CLog::logid();

        }
        
		// 检查用户登录
		if (isset($this->action_params['checkLogin']) && 
			true == $this->action_params['checkLogin']) {
			$this->user_info = CheckLogin::getInstance()->checkLoginUser($this->requests);
		}

		//检查限流
		if(CommonConfig::CHECK_RESTRICT)
		{
			if(isset($_SERVER['REQUEST_URI']))
			{
				$uri = $_SERVER['REQUEST_URI'];
				$pos = strpos($uri, "?");
				if(false !== $pos)
				{
					$uri = substr($uri, 0, $pos);
				}
				if(isset(CommonConfig::$arrRestrictUrl[$uri]))
				{
					$restrict_level = CommonConfig::$arrRestrictUrl[$uri];
					$random = mt_rand(0,100);
					if($random > $restrict_level)
					{
						throw new Exception("Common.reject server reject this request now");
					}
				}
			}else {
				throw new Exception("Common.apinotsupport REQUEST_URI not exist, bad request");
			}
		}
		return true;
	}
	
	/**
	 * @param Context $context	Bingo Context instance
	 * @param array $actionParams	Params for current Action
	 * @return bool
	 */
	public function execute(Context $context, array $actionParams = array())
	{
		$this->context_params = $context->getProperty(self::CONTEXT_PARAMS);
		if (!$this->context_params) {
			$this->context_params = array();
		}
		
	    $ret = $this->doExecute();
		$context->setProperty(self::CONTEXT_PARAMS, $this->context_params);
		
		return $ret;
	}
	
	/**
	 * Actions extends from BaseAction should override the doPost() or doGet()
	 * interface, or directly override this interface, or directly override
	 * the execute() interface.
	 * 
	 * @return bool
	 */
	protected function doExecute()
	{
		if ($this->checkCsrfAttack()) {
			$this->doCsrfAttackPrevention();
			return false;
		}
		
		switch(strtoupper($_SERVER['REQUEST_METHOD'])) {
			case 'POST':
				return $this->doPost();
				
			case 'PUT':
				return $this->doPut();

			case 'DELETE':
				return $this->doDelete();
				
			default:
				return $this->doGet();
		}
	}
	
	/**
	 * Actions extends from BaseAction and is designed for PUT request should
	 * override this interface, or directly override the doExecute() interface.
	 * 
	 * @return bool
	 */
	protected function doPut()
	{
		return true;
	}
	
	/**
	 * Actions extends from BaseAction and is designed for POST request should
	 * override this interface, or directly override the doExecute() interface.
	 * 
	 * @return bool
	 */
	protected function doPost()
	{
		return true;
	}

	/**
	 * Actions extends from BaseAction and is designed for DELETE request should
	 * override this interface, or directly override the doExecute() interface.
	 * 
	 * @return bool
	 */
	protected function doDelete()
	{
		return true;
	}

	/**
	 * Actions extends from BaseAction and is designed for GET request should
	 * implement this interface, or directly implement the doExecute() interface.
	 * 
	 * @return bool
	 */
	protected function doGet()
	{
		return true;
	}
	
	/**
	 * Check whether the request is a csrf attack request, any non-GET request
	 * and any GET request specified to need bkstoken should do csrf attack prevention.
	 * 
	 * @return bool
	 */
	protected function checkCsrfAttack()
	{
		if ($this->is_debug && intval($_GET['no_csrf']) == 1) {
			return false;
		}
		if (!isset($this->context_params['bkstoken'])) {
			$this->context_params['bkstoken'] = BacklinkCsrfToken::getBkstoken();
		}

		$need_bkstoken = isset($this->action_params['need_bkstoken'])
			&& (bool)$this->action_params['need_bkstoken']
			|| (strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') !== 0);
			
		$is_bkstoken_valid = isset($_REQUEST['bkstoken'])
			&& $_REQUEST['bkstoken'] === $this->context_params['bkstoken'];
		
		if ($need_bkstoken && !$is_bkstoken_valid) {
			CLog::warning('bkstoken invalid: ' . $_REQUEST['bkstoken']);
			$this->setErr(CommonConst::EC_CM_INVALID_BKSTOKEN);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Actions extend from BaseAction should override this interface when
	 * need some customized processing for csrf attack.
	 */
	protected function doCsrfAttackPrevention()
	{
		$this->output403Page();
	}

	/**
	 * @param int $errno
	 * @param string $errmsg
	 */
	protected function setErr($errno, $errmsg = '')
	{
		$this->context_params['errno'] = $errno;
		$this->context_params['errmsg'] = $errmsg;
	}

	/**
	 * Get last error no.
	 * 
	 * @return int
	 */
	protected function errno()
	{
		return isset($this->context_params['errno']) ?
			intval($this->context_params['errno']) : 0;
	}

	/**
	 * Get last error message.
	 * 
	 * @return string
	 */
	protected function errmsg()
	{
		return isset($this->context_params['errmsg']) ?
			$this->context_params['errmsg'] : '';
	}
	
	/**
	 * Set Cache-Control and Expires header for response to use browser cache.
	 * 
	 * @param int $timeout Seconds to timeout
	 * @return void
	 */
	protected function setBrowserCache($timeout)
	{
		header('Cache-Control: max-age=' . $timeout);
		header('Expires: ' . gmdate('D, d M Y H:i:s', $timeout + time()) . ' GMT');
	}
	
	/**
	 * Set Cache-Control and Pragma header to avoid browser cache
	 * 
	 * @return void
	 */
	protected function setNoBrowserCache()
	{
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');	
	}
	
	/**
	 * Set Content Type header for response
	 * example:
	 * <code>
	 * 	$this->setContentType('text/html');
	 * </code>
	 * 
	 * @param string $mime_type Content type value, no need to specify charset
	 * @return void
	 */
	protected function setContentType($mime_type)
	{
		header("Content-Type: $mime_type;charset=$this->charset");
	}
	
	
	
	/**
	 * Get refer url.
	 * 
	 * @return string
	 */
	protected function getRefer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}

	/**
	 * Build query string.
	 * 
	 * @param array $params
	 * @return string
	 */
	protected function buildQueryString(array $params)
	{
		foreach ($params as $key => $val) {
			if (!$val) {
				unset($params[$key]);
			}
		}
		return http_build_query($params);
	}
	
	/**
	 * Whether is a https request or not
	 * 
	 * @return bool
	 */
	protected function isHttpsRequest()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$scheme = 'https';
		}
		return ($scheme == 'https');
	}
	
	/**
	 * Redirect to the related url in https scheme if it's a http get request,
	 * or directly exit if it's a http post request.
	 * 
	 * @return void
	 */
	protected function forceToHttps()
	{
		if (!$this->isHttpsRequest()) {
			if (strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') !== 0) {
				CLog::warning('exit as it should be https');
				CLog::notice("MVC-FRAMEWORK execute complete, ret_code=[%s] request done", CommonConst::EC_CM_PARAM_ERROR);
				exit();
			} else {
				CLog::notice('redirect to use https');
				$this->redirectAndExit('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			}
		}
	}
	
	/**
	 * Output the response for async request.
	 * 
	 * @param mixed $value
	 * @return void
	 */
	protected function outputAsyncResponse($value)
	{
		$json = json_encode($value);
		
		$callback = isset($_GET['callback']) ? preg_replace('/[^\w\._()]/', '', $_GET['callback']) : '';
		
		if ($callback) {
			$this->setContentType('text/javascript');
			echo "$callback($json)";
		} else {
			$this->setContentType('application/json');
			echo $json;
		}
	}
	
	protected function outputResponse()	{
		$this->outputAsyncResponse($this->outputs);
	}
	
	protected function set($key, $value = null) {
		$this->outputs [$key] = $value;
	}
	
	/**
	 * Output 403 error page.
	 */
	protected function output403Page()
	{
		CLog::notice("MVC-FRAMEWORK execute complete, ret_code=[%s] request done", CommonConst::EC_CM_NO_PERMISSION);
		header('HTTP/1.1 403 Forbidden');
		exit();
	}
	
	/**
	 * Redirect to the specified URL and exit the php process
	 * 
	 * @param string $url
	 * @return void
	 */
	protected function redirectAndExit($url)
	{
		CLog::notice("MVC-FRAMEWORK execute complete, time cost[".bdTimer::toString() . "] ret_code=[0] request done");
		header("Location: $url");
		exit();	
	}

    // error or exception handler
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
		
		header('HTTP/1.1 500');
		$this->set("request_id", $this->logid);
		$this->set("error_code", CommonConst::EC_CM_SERVICE_INVALID);
		$this->set("error_msg", CommonConst::$errorDescs[CommonConst::EC_CM_SERVICE_INVALID]);
		CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', CommonConst::EC_CM_SERVICE_INVALID);
		$this->outputResponse();
		exit;
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
		$config = ucfirst($action) . 'Config';
        
        $ReflectionClass = new ReflectionClass($config);
        $arrExceptionReturnMap = $ReflectionClass->getStaticPropertyValue('arrExceptionReturnMap');
        	
	    if(!array_key_exists($errcode, $arrExceptionReturnMap)) {
			$errcode = $action . '.internal';
		}
	
		$httpCode = $arrExceptionReturnMap[$errcode]['httpCode'];
		header("HTTP/1.1 $httpCode");
		$this->set("request_id", $this->logid);
		$this->set("error_code", $arrExceptionReturnMap[$errcode]['errno']);
		$this->set("error_msg", CommonConst::$errorDescs[$arrExceptionReturnMap[$errcode]['errno']]);
		CLog::warning('Caught exception, error_code:%s, trace:%s', $errcode, $ex->__toString());
		CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', $arrExceptionReturnMap[$errcode]['errno']);
	}

	public function exceptionHandler($ex) {
		$this->_exceptionHandler($ex);
		if ((defined('IS_DEBUG') && IS_DEBUG)) {
			echo "<pre>\n";
			print_r($ex->__toString());
			echo "\n</pre>";
		}
		$this->outputResponse();
		exit;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
