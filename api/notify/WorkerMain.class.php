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
	set_error_handler('errorHandler');
	
	// 设置log_id
	CLog::setLogId($arr_task_info['logid']);
	
	//NotifyWorker::doExecute($arr_task_info);
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

function get_task($receiver)
{
	// 获取任务
	$arr_receive_data = $receiver->get_task();
	if (false === $arr_receive_data || 
		!is_array($arr_receive_data) ||
		!isset($arr_receive_data['header']) ||
		!isset($arr_receive_data['body']))
	{
		CLog::warning("get task failed");
		return false;
	}
	
	// 未获取到任务, 返回false，但不打日志，避免线上空闲时，无效日志刷屏
	if (0 == count($arr_receive_data))
	{
		return array();	
	}
	
	$task_info_str = $arr_receive_data['body'];
	$arr_task_info = json_decode($task_info_str, true);
	if (false == $arr_task_info || !is_array($arr_task_info))
	{
		CLog::warning("invalid task [task_info_str: %s]", $task_info_str);
		return false;
	}
	
	$arr_task_info['logid'] = $arr_receive_data['header']['log_id'];
	
	CLog::trace("get task succ [logid: %s, pid: %s, user_id: %s, phone: %s, ctime: %s, " .
				"mtime: %s, price: %s, mileage: %s, src: %s, dest: %s, " . 
				"src_gps: %s, dest_gps: %s, timeout: %s]", 
				$arr_task_info['logid'], $arr_task_info['pid'], 
				$arr_task_info['user_id'], $arr_task_info['phone'], 
				$arr_task_info['ctime'],$arr_task_info['mtime'],
				$arr_task_info['price'],$arr_task_info['mileage'], 
				$arr_task_info['src'],$arr_task_info['dest'], 
				$arr_task_info['src_gps'],$arr_task_info['dest_gps'], 
				$arr_task_info['timeout']);
	
	return $arr_task_info;
}

function main()
{
	$child_process_num = WorkerConfig::$childProcessNum;
    $ipc_receiver = new PHPIpcReceiver(IPCConfig::$domain_info);
    $arr_pid = array();
    
    $is_pause_get_task = false;
    $is_pause_wait_pid = false;
    
    // 优先获取任务，只有当任务为空或可处理进程个数已满时才跳出循环，进入回收子进程的流程
	while(true)
	{
		while(true)
		{
			usleep(WorkerConfig::WORKER_SLEEP_TIME);
	
			// 若无子进程，则父进程做逻辑
			if (0 >= $child_process_num)
			{
				$arr_task_info = get_task($ipc_receiver);
				if (false !== $arr_task_info &&
					is_array($arr_task_info) &&
					0 != count($arr_task_info))
				{
					execute($arr_task_info);
				}
				
				continue;
			}
			else // 开启子进程模式
			{
				// 有空闲子进程
				if (count($arr_pid) < $child_process_num)
				{
					// 获取任务
					$arr_task_info = get_task($ipc_receiver);
					
					// 若获取任务失败或者无任务，则跳出循环
					if (false === $arr_task_info ||
						!is_array($arr_task_info) ||
						0 == count($arr_task_info))
					{
						$is_pause_get_task = true;
					}
					else
					{
						$pid = pcntl_fork();
						if (-1 == $pid)
						{
							CLog::fatal("pcntl_fork() child prcess failed");
							continue;
						}
						else if (0 == $pid) // 子进程
						{
							execute($arr_task_info);
							exit(0);
						}
						else // 父进程
						{
							// 记录pid对应的logid
							$arr_pid[$pid] = $logid;
						}
					}
				}
				else
				{
					CLog::warning("there is no available process resources");
					$is_pause_get_task = true;
				}
			}
			
			if (true == $is_pause_get_task)
			{
				break;
			}
		}
		
		// 回收子进程
		if (0 >= $child_process_num)
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
				} 
				else
				{
					$logid = $arr_pid[$pid];
					unset($arr_pid[$pid]);
					//$exitstatus = pcntl_wexitstatus($status);
				}
				
			    // 计算执行的时间片
	            $time_now = gettimeofday();
	            $time_used = ($time_now['sec'] - $time_start['sec']) * 1000000 + ($time_now['usec'] - $time_start['usec']);
	            if ($time_used > WorkerConfig::WORKER_WAWIT_PID_TIME)
	            {
	                $is_pause_wait_pid = true;
	            }
	            
	            if (true === $is_pause_wait_pid)
	            {
	            	break;
	            }
			}
		}
		
	}
}
 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
