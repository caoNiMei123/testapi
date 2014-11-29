<?php

class LogicBaseAction extends BaseAction
{
	protected $logid = 0;
	protected $outputs = array();
	
	public function initial($initObject)
	{
		parent::initial($initObject);
		return true;
	}
	
	public function execute(Context $context, array $actionParams = array())
	{
		$this->context_params = $context->getProperty(self::CONTEXT_PARAMS);
		if (!$this->context_params) {
			$this->context_params = array();
		}
        
		$this->set('request_id', $this->logid);
		
		if("GET" == $_SERVER['REQUEST_METHOD']) {
			$this->doGet();
		}elseif ("POST" == $_SERVER['REQUEST_METHOD']) {
			$this->doPost();
		}else {
			CLog::warning("http_method[" . $_SERVER['REQUEST_METHOD'] . "] not allowed to use this api");
			$this->errorDispatch();
		}

		$this->outputResponse();
	}
	
	public function doGet()
	{
		CLog::warning("http_method[" . $_SERVER['REQUEST_METHOD'] . "] not allowed to use this api");
		$this->errorDispatch();
	}
	
	public function doPost()
	{
		CLog::warning("http_method[" . $_SERVER['REQUEST_METHOD'] . "] not allowed to use this api");
		$this->errorDispatch();
	}

    protected function errorDispatch($http_code = 404)
    {
        $logid = CLog::logId();
        if($http_code == 404) {
        	$errcode = CommonConst::EC_CM_API_UNSUPPORTED;
        }elseif ($http_code == 400) {
        	$errcode = CommonConst::EC_CM_PARAM_ERROR;
        }elseif($http_code == 403) {
        	$errcode = CommonConst::EC_CM_NO_PERMISSION;
        }
        $errmsg = CommonConst::$errorDescs[$errcode];
        CLog::notice('MVC-FRAMEWORK execute complete, ret_code=[%s] request done', $errcode);   
        $retMsg = array();
        $retMsg['request_id'] = $logid;
        $retMsg['error_code'] = $errcode;
        $retMsg['error_msg'] = $errmsg;

        header("HTTP/1.1 $http_code");
        header("Content-Type: application/json;charset=utf-8");
		
		$json = json_encode($retMsg);
        echo $json;
        exit;
    }
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
