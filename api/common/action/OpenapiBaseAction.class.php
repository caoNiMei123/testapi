<?php

class OpenapiBaseAction extends BaseAction
{
	protected $logid = 0;
	protected $outputs = array();
	
	public $uid = 0;
	public $dev_uid = 0;
	public $appid = 0;
	
    public function _before()
    {
    	if(true === CommonConfig::CHECK_OPENAPI_HTTPS && false === $this->isHttpsRequest()) {
			throw new Exception("Common.auth openapi only allow https interface");
		}
        if(!isset($this->requests['access_token'])) {
        	throw new Exception("Common.param access_token param not exist in openapi interface ");
        }
        $access_token = $this->requests['access_token'];
        bdTimer::start("openapi-ident-access_token");
        $uasProxy = UasProxy::getInstance();
        $arr_res = $uasProxy->identToken($access_token);
        if(false === $arr_res || 0 >= $arr_res['uid']) {
        	throw new Exception("Common.internal call uas to ident access_token failed, error info[" . $uasProxy->getErrorInfo() . "]");
        }
        $this->uid = intval($arr_res['uid']);
        $this->dev_uid = intval($arr_res['devUid']);
        $this->appid = intval($arr_res['appid']);
        CLog::debug("ident access_token from uas success, uid[%s] appid[%s] dev_uid[%s]", $this->uid, $this->appid, $this->dev_uid);
        bdTimer::end("openapi-ident-access_token");
    }

    public function _after()
    {
		$this->set('request_id', $this->logid);
		$this->outputResponse();
    }
    
	public function initial($initObject)
	{
		parent::initial($initObject);
		return true;
	}
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
