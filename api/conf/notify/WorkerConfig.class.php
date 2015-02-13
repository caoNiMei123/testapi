<?php

class WorkerConfig
{
	// 子进程数量，0:表示不使用子进程，直接使用父进程进行工作；>0:表示启动的子进程数量
	public static $childProcessNum = 1;
	
	// 任务队列容量
	public static $taskQueueCapacity = 10000;
	
	// 没有任务时的休眠时间，单位: 微秒
	const WORKER_SLEEP_TIME = 10000;
	
	// 获取任务的时间片，单位: 微秒
	const WORKER_PRODUCE_TIME = 10000;
	
	// 回收子进程的处理时间片，单位: 微秒
	const WORKER_CLEAN_TIME = 10000;
	
	// 任务资源日志刷新间隔
	const WORKER_LOG_NUM = 800;
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */