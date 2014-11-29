<?php

/**
 * Action controller, an action of providing uri router service.
 */
class ActionController extends BaseAction
{
	protected $ruleConfig = array();
	protected $hashMapping = array();
	protected $prefixMapping = array();
	protected $regexMapping = array();
	
	/**
	 * Initialize the action controller
	 * 
	 * @param array $config Uri router config
	 * @return bool
	 */
	public function initial(array $config)
	{
		$this->ruleConfig = isset($config['rule_config']) ? $config['rule_config'] : array();
		$this->hashMapping = isset($config['hash_mapping']) ? $config['hash_mapping'] : array();
		$this->prefixMapping = isset($config['prefix_mapping']) ? $config['prefix_mapping'] : array();
		$this->regexMapping = isset($config['regex_mapping']) ? $config['regex_mapping'] : array();

		parent::initial(null);
		return true;
	}

	/**
	 * Start execution of the action controller.
	 * 
	 * @param Context $context Context object for all the actions in action chain
	 * @param array $actionParams Params for the action
	 * @return bool Ture if the action has finish the request proccessing, or false if otherwise
	 */
	public function execute(Context $context, array $actionParams = array())
	{
		$info = $this->getDispatchedActionInfo($context);
		if ($info) {
			if (is_array($info[1])) {
				$actionParams = array_merge($info[1], $actionParams);
			}
		    // added by zl 对openapi，利用method进行路由
		    if (isset($info[2]) && 'openapi' === $info[2])
		    {
		        return $this->openapiDispath($info[0]);
		    }
		    // added by zl
			return $context->callAction($info[0]->actionClassName, $actionParams);
		}
		return false;
	}

	/**
	 * Get the dispatched action's config
	 * @param Context $context
	 * @return array
	 */
	private function getDispatchedActionInfo(Context $context)
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = '';
		}
		
		$ignoredDirs = isset($this->ruleConfig['begindex']) ? intval($this->ruleConfig['begindex']) : 0;
		$parsedUri = $this->parseRequestUri($uri, $ignoredDirs);
		
		// Always use hash mapping rules to dispatch uri as the first selection
		/*
		 * hash匹配
		 * 1. 没有dispatch返回，表示根据uri就能确定action
		 * 2. 有dispatch返回，值为openapi，表示rest风格api，按method区分action，并且所有
		 * 	    的action都在同一个文件中
		 * 3. 有dispatch返回，值为openapi2，同2，只是所有的action在不同文件中
		 */
		if (isset($this->hashMapping[$parsedUri])) {
			$actionConfig = $this->hashMapping[$parsedUri];
			
            // added by zl 情况2 openapi
			if (isset($actionConfig['dispatch']) && 'openapi' === $actionConfig['dispatch'])
			{
				$actionParams = isset($actionConfig[1]) ? $actionConfig[1] : array();
				$action = $context->getAction($actionConfig[0], $actionParams);
			    return array($action, null, 'openapi');
			}
			else if (isset($actionConfig['dispatch']) && 'openapi2' === $actionConfig['dispatch']) // 情况3 openapi2
			{
			    if (isset($this->requests['method']) &&
			    	isset($actionConfig['method_hash'][$this->requests['method']]))
			    {
					$methodActionConfig = $actionConfig['method_hash'][$this->requests['method']];
					$actionParams = isset($methodActionConfig[1]) ? $methodActionConfig[1] : array();
					$action = $context->getAction($methodActionConfig[0], $actionParams);
					
					return array($action, null);
			    }
			}
			else // 情况1
			{
				$actionParams = isset($actionConfig[1]) ? $actionConfig[1] : array();
				$action = $context->getAction($actionConfig[0], $actionParams);
				return array($action, null);
			}
			// added by zl
		}
		
		// If no hash mapping rule matched, use prefix mapping rules as the second selection
		foreach ($this->prefixMapping as $pattern => $actionConfig) {
			if (strpos($parsedUri, $pattern) === 0) {
				$actionParams = isset($actionConfig[1]) ? $actionConfig[1] : array();
				$action = $context->getAction($actionConfig[0], $actionParams);
				return array($action, null);
			}
		}
		
		// Use regex mapping rule as the last selection
		foreach ($this->regexMapping as $pattern => $actionConfig) {
			if (preg_match($pattern, $uri, $matches)) {
				$actionParams = isset($actionConfig[1]) ? $actionConfig[1] : array();
				$action = $context->getAction($actionConfig[0], $actionParams);
				return array($action, $matches);
			}
		}

		// added by zl 路由失败 设置http code
		$this->errorDispatch();
		// added by zl

		$errmsg = 'No action could be dispatched for uri: ' . $uri;
		trigger_error($errmsg, E_USER_WARNING);
		
		return null;
	}

	/**
	 * Parese request uri and ignore some prefix dirs.
	 * 
	 * @param string $uri Uri to be paresed
	 * @param int $ignoredDirs How many dirs to be ignored
	 * @return string
	 */
	private function parseRequestUri($uri, $ignoredDirs = 0)
	{
		if (!isset($ignoredDirs) || $ignoredDirs < 0) {
			$ignoredDirs = 0;
		}
		
		$path = explode('?', $uri);
		$path = explode('/', $path[0]);
		
		$dirs = array();
		foreach ($path as $value) {
			$value = trim($value);
			if ('' === $value) {
				continue;
			}
			$dirs[] = $value;
		}
		
		$dirs = array_slice($dirs, $ignoredDirs);
		$uri = '/' . implode('/', $dirs);
		
		return strtolower($uri);
	}

    // added by zl
    private function errorDispatch()
    {
        $logid = CLog::logId();
        $errcode = CommonConst::EC_CM_API_UNSUPPORTED;
        $errmsg = CommonConst::$errorDescs[$errcode];
        
        CLog::warning('Caught exception, error_code:%s, error_msg:%s, function:%s', $errcode, $errmsg, __FUNCTION__);
        
        $retMsg = array();
        $retMsg['request_id'] = $logid;
        $retMsg['error_code'] = $errcode;
        $retMsg['error_msg'] = $errmsg;
		CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', $errcode);
        header("HTTP/1.1 404");
        header("Content-Type: application/json;charset=utf-8");
		
		$json = json_encode($retMsg);
        echo $json;
        exit;
    }

    private function callmethod($action, $method, $methodParams = array())
    {
        if (is_callable(array($action, $method)))
        {
            $reflection = new ReflectionMethod($action, $method);
            $argnum = $reflection->getNumberOfParameters();
            if ($argnum > count($methodParams))
            {
                $this->errorDispatch();
            }
            
            $reflection->invokeArgs($action, $methodParams);
            return true;
        }
        
        $this->errorDispatch();
    }

    // 对于openapi，利用method进行路由
    private function openapiDispath($action)
    {
        $this->requests = array_merge($_GET, $_POST);
        if (!isset($this->requests['method']))
        {
            $this->errorDispatch();
        }

        $method = $this->requests['method'];
        
        $this->callmethod($action, '_before');
        if ($this->callmethod($action, $method))
        {
            $this->callmethod($action, '_after');
            return true;
        }
        
        $this->errorDispatch();
    }
    // added by zl
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
