<?php

class EmailPorxy
{
	private static $instance = NULL;
	private const AUTH_TITLE = '【易拼车】乘客邮箱认证';
	private const AUTH_MESSAGE = "尊敬的用户，您好：\n感谢您使用易拼车，请点击以下链接激活邮箱\n<a href=%s></a>";
	
	/**
	 * @return PushPorxy
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new EmailPorxy();
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
		//$headers = "From: webmaster@example.com" . "\r\n" 
		mail($email, self::AUTH_TITLE, $message);
		return true;
		
	}
	
	
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
