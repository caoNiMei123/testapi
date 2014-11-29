<?php

class WorkerConfig
{
	// 子进程数量，0:表示不使用子进程，直接使用父进程进行工作；>0:表示启动的子进程数量
	public static $childProcessNum = 1;

	// 是否重启子进程循环工作，true:一直循环工作; false:工作一次后退出
	public static $restartChild = true;
	
	public static $mailReport = false; 
    public static $mailSubject = '';
    public static $mailTo = '';
	public static $mailFrom = '';
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */