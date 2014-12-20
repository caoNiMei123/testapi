<?php

class SmsPorxy
{
	private static $instance = NULL;
	const SMS_URL = 'http://api.weimi.cc/2/sms/send.html';
	const SMS_UID = 'hhr18AHagk5K';
	const SMS_PWD = 'j573xk4a';
	const SMS_CID = 'kMChtX3UZ1YY';
	

	/**
	 * @return PushPorxy
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new SmsPorxy();
		}
		
		return self::$instance;
	}

	protected function __construct()
	{
		
	}
	
	/*
	 * 发送短信给单个手机号	 
	 */
	public function push_to_single($phone,$msg)	{

		$http_proxy = HttpProxy::getInstance();

		$params = array(
			'uid' => self::SMS_UID,
			'pas' => self::SMS_PWD,
			'cid' => self::SMS_CID,
			'mob' => strval($phone),
			'p1' => $msg,
			'type'=>'json',
		);
		$response = $this->httpProxy->initRequest( self::SMS_URL, array() )->post( $params);
		if (!$response) {
			return false;
		} 
		$arr_response = json_decode($response, true);
		if (!$arr_response || !is_array($arr_response)) {
			return false;
		}
		if ($arr_response['code'] != 0) {			
			return false;
		}
		return true;
		
	}
	
	
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
