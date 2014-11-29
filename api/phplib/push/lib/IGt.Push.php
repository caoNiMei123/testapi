<?php

require_once(dirname(__FILE__) . '/' . 'protobuf/pb_message.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.Req.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.Message.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.AppMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.ListMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.SingleMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.Target.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.BaseTemplate.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.LinkTemplate.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.NotificationTemplate.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.TransmissionTemplate.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.NotyPopLoadTemplate.php');

Class IGeTui
{
    var $appkey; //第三方 标识
    var $masterSecret; //第三方 密钥
    var $debug = true;
    var $format = "json"; //默认为 json 格式
    var $host = '';
    var $needDetails = false;


    public function __construct($host, $appkey, $masterSecret)
    {
        $this->host = $host;
        $this->appkey = $appkey;
        $this->masterSecret = $masterSecret;
        //$this->connect();
    }

    function connect()
    {
        $timeStamp = $this->micro_time();
        // 计算sign值
        $sign = md5($this->appkey . $timeStamp . $this->masterSecret);
        //
        $params = array();

        $params["action"] = "connect";
        $params["appkey"] = $this->appkey;
        $params["timeStamp"] = $timeStamp;
        $params["sign"] = $sign;
        $data = $this->createParam($params);
        $result = $this->httpPost($data);
        $rep = json_decode($result, true);
        if ('success' == $rep['result']) {
            return true;
        }
        throw new Exception("appKey Or masterSecret is Auth Failed");
        return false;
    }
    
    // added by zl
    public function set_debug($is_debug)
    {
    	$this->debug = $is_debug;
    }
    // added by zl
    
    /**
     *  指定用户推送消息
     * @param  IGtMessage message
     * @param  IGtTarget target
     * @return Array {result:successed_offline,taskId:xxx}  || {result:successed_online,taskId:xxx} || {result:error}
     ***/
    public function pushMessageToSingle($message, $target)
    {
        $params = array();

        $params["action"] = "pushMessageToSingleAction";
        $params["clientData"] = base64_encode($message->get_data()->get_transparent());
        $params["transmissionContent"] = $message->get_data()->get_transmissionContent();
        $params["isOffline"] = $message->get_isOffline();
        $params["offlineExpireTime"] = $message->get_offlineExpireTime();

        //
        $params["appId"] = $target->get_appId();
        $params["clientId"] = $target->get_clientId();
        // 默认都为消息
        $params["type"] = 2;
        $params["pushType"] = $message->get_data()->get_pushType();

        return $this->httpPostJSON($params);
    }

    /**
     * 获取消息ID
     * @param  IGtMessage message
     * @return String contentId
     ***/
    public function getContentId($message)
    {
        $params = array();

        $params["action"] = "getContentIdAction";
        $params["clientData"] = base64_encode($message->get_data()->get_transparent());
        $params["transmissionContent"] = $message->get_data()->get_transmissionContent();
        $params["isOffline"] = $message->get_isOffline();
        $params["offlineExpireTime"] = $message->get_offlineExpireTime();

        $params["pushType"] = $message->get_data()->get_pushType();
        
        $rep = $this->httpPostJSON($params);

        return $rep['result'] == 'ok' ? $rep['contentId'] : '';
    }


    /**
     *  取消消息
     * @param  String  contentId
     * @return boolean
     ***/
    public function cancleContentId($contentId)
    {

        $params = array();

        $params["action"] = "cancleContentIdAction";
        $params["contentId"] = $contentId;


        $rep = $this->httpPostJSON($params);

        return $rep['result'] == 'ok' ? true : false;
    }

    /**
     *  批量推送信息
     * @param  String contentId
     * @param  Array <IGtTarget> targetList
     * @return Array {result:successed_offline,taskId:xxx}  || {result:successed_online,taskId:xxx} || {result:error}
     ***/
    public function pushMessageToList($contentId, $targetList)
    {

        $params = array();

        $params["action"] = "pushMessageToListAction";
        $params["contentId"] = $contentId;
        $params["targetList"] = $targetList;
        $params["type"] = 2;
		$needDetails = getenv("needDetails");
		if('true'==$needDetails) {
			$params["needDetails"] = true;
		} else {
			$params["needDetails"] = false;
		}
        
        return $this->httpPostJSON($params);
    }

    /**
     *  指定应用推送消息
     * @param  AppMessage message
     * @return Array {result:successed_offline,taskId:xxx}  || {result:successed_online,taskId:xxx} || {result:error}
     ***/
    public function pushMessageToApp($message)
    {
        $params = array();

        $params["action"] = "pushMessageToAppAction";
        $params["clientData"] = base64_encode($message->get_data()->get_transparent());
        $params["transmissionContent"] = $message->get_data()->get_transmissionContent();
        $params["isOffline"] = $message->get_isOffline();
        $params["offlineExpireTime"] = $message->get_offlineExpireTime();

        //
        $params["appIdList"] = $message->get_appIdList();
        $params["phoneTypeList"] = $message->get_phoneTypeList();
        $params["provinceList"] = $message->get_provinceList();
        $params["tagList"] = $message->get_tagList();

        // 默认都为消息
        $params["type"] = 2;
        $params["pushType"] = $message->get_data()->get_pushType();

        return $this->httpPostJSON($params);
    }

    public function stop($contentId)
    {
        $params = array();

        $params["action"] = "stopTaskAction";
        $params["appkey"] = $this->appkey;
        $params["contentId"] = $contentId;

        try {
            $rep = $this->httpPostJSON($params);
            if ("ok" == $rep["result"]) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception("host:[" + $this->host + "]" + "取消任务失败：" + $e->getTraceAsString());
        }
        return false;
    }

    public function getClientIdStatus($appId, $clientId)
    {
        $params = array();

        $params["action"] = "getClientIdStatusAction";
        $params["appkey"] = $this->appkey;
        $params["appId"] = $appId;
        $params["clientId"] = $clientId;

        try {
            return $this->httpPostJSON($params);
        } catch (Exception $e) {
            throw new Exception("host:[" + $this->host + "]" + "获取用户状态失败：" + $e->getTraceAsString());
        }
    }

    private function debug($log)
    {
        if ($this->debug)
        {
        	CLog::debug("push log: %s", $log);
            //echo ($log) . "\r\n";
        }
    }

    private function micro_time()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3)) + 0;
        return $time;
    }

    private function httpPostJSON($params)
    {
        $params["version"] = "3.0.0.0";
        $data = $this->createParam($params);
        $result = $this->httpPost($data);
        $rep = json_decode($result, true);


        if ($rep['result'] == 'sign_error') {
            if ($this->connect())
                return $this->httpPostJSON($params);
        }
        return $rep;
    }

    private function createParam($params)
    {
        $params['appkey'] = $this->appkey;
        if ($this->format == 'json') {
            return json_encode($params);
        }
    }

    private function createSign($params)
    {
        $sign = null;
        foreach ($params as $key => $val) {
            if (isset($key) && isset($val)) {
                if (is_string($val) || is_numeric($val)) { // 针对非 array object 对象进行sign
                    $sign .= $key . ($val); //urldecode
                }
            }
        }
        $sign = md5($sign);
        return $sign;
    }

    private function httpPost($data)
    {
        $curl = curl_init($this->host);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'GeTui PHP/1.0');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //通过代理访问接口需要在此处配置代理
        //curl_setopt($curl, CURLOPT_PROXY, '192.168.1.18:808');
        //请求失败有3次重试机会
        $result = $this->exeBySetTimes(3, $curl);
        curl_close($curl);
        $this->debug("发送请求 post:{$data} return:{$result}");
        return $result;
    }

    private function exeBySetTimes($count, $curl)
    {
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->debug("请求错误: " . curl_errno($curl));

            if ($count > 0) {
                $count--;
                $result = $this->exeBySetTimes($count, $curl);
            }
        }
        return $result;
    }
}