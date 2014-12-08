<?php

class EmailProxy
{
	private static $instance = NULL;
	const AUTH_TITLE = '【易拼车】乘客邮箱认证';
	const AUTH_MESSAGE = "<html><head></head><body><p>尊敬的用户，您好：</p><p>感谢您使用易拼车，请点击以下链接激活邮箱</p><p><a href=\"%s\">点击激活</a></p></body></html>";
	
	/**
	 * @return PushPorxy
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new EmailProxy();
		}
		
		return self::$instance;
	}

	protected function __construct()
	{
		
	}
	
	/*
	 * 发送认证邮件	 
	 */
	public function auth($email, $url)	{
		$message = sprintf(self::AUTH_MESSAGE, $url);		
		$headers="MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=utf-8\r\n";
		$headers .="From: =?UTF-8?B?".base64_encode("易拼车")."?= <no-reply@yipinche.com>\r\n";			
		$headers .="Return-Path: no-reply@yipinche.com\r\n";		
		$header .= "Content-Transfer-Encoding: 8bit\r\n";	     
		
		mail($email,  "=?UTF-8?B?".base64_encode(self::AUTH_TITLE)."?=",  $message, $headers);
		return true;
		
	}
	
	
}

