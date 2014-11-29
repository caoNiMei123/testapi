<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Phplib{

	function __construct($params=array()){
		if(empty($params)){
			$env = "offline";
		}else{
			$env = $params[0];
		}
		
		if(file_exists(BASEPATH."../phplib/offline") && $env == "offline"){
			define("PHPLIB_PATH", BASEPATH."../phplib/offline");
		}else if(file_exists(BASEPATH."../phplib/online") && $env == "online"){
			define("PHPLIB_PATH", BASEPATH."../phplib/online");
		}
		
	}
	
	public function importAll(){
		
		$this->loadPassport();
		
	}
	
	
	public function importHttpproxy(){
		require_once PHPLIB_PATH."/httpproxy/HttpProxy.class.php";
		$this->httpproxy = HttpProxy::getInstance();
	}
	
	public function importHttpclient(){
		require_once PHPLIB_PATH."/log/CLogger.class.php";
		require_once PHPLIB_PATH."/httpclient/HttpClient.class.php";
		$this->httpclient = new HttpClient();
	}
	
	public function importUtf8encode(){
		require_once PHPLIB_PATH."/utils/Utf8Encode.class.php";
		$this->utf8encode = new Utf8Encode();
	}
}
