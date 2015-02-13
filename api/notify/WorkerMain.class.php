<?php

require_once(dirname(__FILE__) .'/common/env_init.php');
require_once('NotifyWorker.class.php');
require_once('NotifyWorker.class.php');

$arr_option = getopt("vhf:n:");
if (isset($arr_option['h']))
{
	help();
}
else if(isset($arr_option['v']))
{
	version();
}
else
{
	$from_timestamp = 0;
	$hour_number = 0;
	
	main($from_timestamp, $hour_number);
}

function execute($arr_task_info)
{
	date_default_timezone_set("Asia/Shanghai");
	error_reporting(E_ALL|E_STRICT);
	set_exception_handler('exceptionHandler');
	
	// 不捕获php语法错误
	//set_error_handler('errorHandler');
	
	// 设置log_id
	CLog::setLogId($arr_task_info['logid']);

	CLog::trace("execute task begin [logid: %s, pid: %s, user_id: %s, phone: %s, ctime: %s, " .
				"mtime: %s, price: %s, mileage: %s, src: %s, dest: %s, " . 
				"src_gps: %s, dest_gps: %s, timeout: %s]", 
				$arr_task_info['logid'], $arr_task_info['pid'], 
				$arr_task_info['user_id'], $arr_task_info['phone'], 
				$arr_task_info['ctime'],$arr_task_info['mtime'],
				$arr_task_info['price'],$arr_task_info['mileage'], 
				$arr_task_info['src'],$arr_task_info['dest'], 
				$arr_task_info['src_gps'],$arr_task_info['dest_gps'], 
				$arr_task_info['timeout']);
	
	NotifyWorker::doExecute($arr_task_info);
}

function version()
{
	echo ("Version: 1.0.0\n");
}

function help()
{
	echo ("Use command [php WorkerMain.class.php -v] to show the version\n");
	echo ("Use command [php WorkerMain.class.php -h] to show this help list\n");
	echo ("Use command [php WorkerMain.class.php] to start async notify\n");
}

function exceptionHandler($ex)
{
	restore_exception_handler();
	$errmsg = sprintf('caught exception, errcode:%s, trace: %s', $ex->getMessage(), $ex->__toString());
	CLog::fatal($errmsg);
	exit(1);
}

function errorHandler()
{
	restore_error_handler();
	$error = func_get_args();
		
	if (!($error[0] & error_reporting()))
	{
		CLog::debug('caught debug, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
		set_error_handler('errorHandler');
		return ;
	}
	else if ($error[0] === E_USER_NOTICE)
	{
		CLog::trace('caught trace, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
		set_error_handler('errorHandler');
		return ;
	}
	else if ($error[0] === E_USER_WARNING)
	{
		CLog::warning('caught warning, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
		set_error_handler('errorHandler');
		return ;
	}
	else if($error[0] === E_STRICT)
	{
		set_error_handler('errorHandler');
		return ;	
	}
	else
	{
		CLog::fatal('caught fatal, errno:%d,errmsg:%s,file:%s,line:%d',$error[0],$error[1],$error[2],$error[3]);
	}
	
	exit(1);
}

function is_time_up($time_slice, $time_start)
{
	$time_now = gettimeofday();
	$time_used = ($time_now['sec'] - $time_start['sec']) * 1000000 + 
				 ($time_now['usec'] - $time_start['usec']);
	
	return ($time_used > $time_slice ? true : false);
}

function get_task($receiver)
{
	// 获取任务
	$arr_receive_data = $receiver->get_task();
	
	// 获取失败
	if (false === $arr_receive_data || 
		!is_array($arr_receive_data))
	{
		CLog::warning("get task failed");
		return false;
	}
	
	// 任务为空
	if (0 == count($arr_receive_data))
	{
		return array();	
	}

	if (!isset($arr_receive_data['header']) ||
		!isset($arr_receive_data['body']))
	{
		CLog::warning("get task failed");
		return false;
	}
	
	$task_info_str = $arr_receive_data['body'];
	$arr_task_info = json_decode($task_info_str, true);
	if (false == $arr_task_info || !is_array($arr_task_info))
	{
		CLog::warning("invalid task [task_info_str: %s]", $task_info_str);
		return false;
	}
	
	$arr_task_info['logid'] = $arr_receive_data['header']['log_id'];
	
	return $arr_task_info;
}

function producer($receiver, &$arr_task)
{
	// 判断任务队列长度
	if (count($arr_task) > WorkerConfig::$taskQueueCapacity)
	{
		CLog::warning("task queue is full");
		return;
	}
	
	$time_start = gettimeofday();
	
	while(true)
	{
		$arr_task_info = get_task($receiver);
		if (false === $arr_task_info || 
			0 == count($arr_task_info))
		{
			break;
		}
		
		$arr_task[] = $arr_task_info;
		
		// 计算执行的时间片
		if (is_time_up(WorkerConfig::WORKER_PRODUCE_TIME, $time_start))
		{
			break;
		}
	}
}

function consumer(&$arr_task, &$arr_pid)
{
	foreach ($arr_task as $pos => $task_info)
	{
		// 若无子进程，则父进程做逻辑
		if (0 >= WorkerConfig::$childProcessNum)
		{
			execute($task_info);
			unset($arr_task[$pos]);
		}
		else // 开启子进程模式
		{
			// 有空闲子进程
			if (count($arr_pid) < WorkerConfig::$childProcessNum)
			{
				unset($arr_task[$pos]);
				
				$pid = pcntl_fork();
				if (-1 == $pid)
				{
					CLog::fatal("pcntl_fork() child prcess failed");
					continue;
				}
				else if (0 == $pid) // 子进程
				{
					execute($task_info);
					exit(0);
				}
				else // 父进程
				{
					// 记录pid对应的logid
					$arr_pid[$pid] = $logid;
				}
			}
			else // 无空闲子进程退出
			{
				break;
			}
		}
	}
}

function cleaner(&$arr_pid)
{
	if (0 < WorkerConfig::$childProcessNum && 0 < count($arr_pid))
	{
		$time_start = gettimeofday();
		
		while(true)
		{
			$pid = pcntl_waitpid(0, $status, WNOHANG);
			if (0 == $pid)
			{
				//nothing to do
			}
			else if ($pid < 0)
			{
				CLog::fatal("pcntl_waitpid() for process failed");
				break;
			} 
			else
			{
				$logid = $arr_pid[$pid];
				unset($arr_pid[$pid]);
				//$exitstatus = pcntl_wexitstatus($status);
			}
			
		    // 计算执行的时间片
            if (is_time_up(WorkerConfig::WORKER_CLEAN_TIME, $time_start))
            {
                break;
            }
		}
	}
}

function main()
{
	$receiver = new PHPIpcReceiver(IPCConfig::$domain_info);
	
	$arr_task = array();
	$arr_pid = array();
	
	$log_num = 0;
	
	while(true)
	{
		// 1. 生产者
		producer($receiver, $arr_task);
		
		// 2. 消费者
		consumer($arr_task, $arr_pid);
		
		// 3. 清理者
		cleaner($arr_pid);
		
		$log_num++;
		
		// 执行若干次，输出资源日志
		if ($log_num > WorkerConfig::WORKER_LOG_NUM)
		{
			$log_num = 0;
			CLog::trace("worker current status is " . 
						"[task_in_queue: %s, current_process_num: %s, total_child_process_num: %s]", 
						count($arr_task), count($arr_pid), WorkerConfig::$childProcessNum);
		}
	}
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
