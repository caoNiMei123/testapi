<?php

require_once(dirname(__FILE__) .'/common/env_init.php');
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

function execute($cur_process_num, $total_process_num)
{
	date_default_timezone_set("Asia/Shanghai");
	error_reporting(E_ALL|E_STRICT);
	set_exception_handler('exceptionHandler');
	set_error_handler('errorHandler');
	NotifyWorker::doExecute($cur_process_num, $total_process_num);
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

function main($log_file_name = NULL)
{
	$total_process_num = WorkerConfig::$childProcessNum;
	
	// 若无子进程，则父进程做逻辑
	if (0 >= $total_process_num)
	{
		execute(0, 1);
		exit(0);
	}

	$arr_pid = array();
	for ($cur_process_num = 0; $cur_process_num < $total_process_num; $cur_process_num++)
	{
		$pid = pcntl_fork();
		if (-1 == $pid)
		{
			CLog::fatal("pcntl_fork() child prcess failed [cur_process_num: %s]", 
						$cur_process_num);
			continue;
		}
		else if (0 == $pid)
		{
			if (false === execute($cur_process_num, WorkerConfig::$childProcessNum))
			{
				exit(1);
			}
			else
			{
				exit(0);
			}
		}
		else
		{
			// 记录pid对应的进程序号
			$arr_pid[$pid] = $cur_process_num;
		}
	}
	
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
			if (WorkerConfig::$mailReport)
			{
				$subject = WorkerConfig::$mailSubject . date('Y-m-d H:i:s', time());
				$message = "";
				$message .= "on machine [" . gethostname() . "] exit for unknown reason";
				$header = "From: ". WorkerConfig::$mailFrom ."\r\n";
				mail( WorkerConfig::$mailTo, $subject, $message, $header );
			}
		} 
		else
		{
			$cur_process_num = $arr_pid[$pid];
			unset($arr_pid[$pid]);
			
			$exitstatus = pcntl_wexitstatus($status);
			
			//正常退出后，重新生成一个新进程
			if (0 == $exitstatus)
			{
				if(true == WorkerConfig::$restartChild)
				{
					$sub_pid = pcntl_fork();
					if (-1 == $sub_pid)
					{
						CLog::fatal("pcntl_fork() child prcess failed [cur_process_num: %s]", 
									$cur_process_num);
						exit(1);
					}
					else if (0 == $sub_pid)
					{
						if (false === execute($cur_process_num, WorkerConfig::$childProcessNum))
						{
							exit(1);
						}
						else
						{
							exit(0);
						}
					}
					else
					{
						$arr_pid[$sub_pid] = $cur_process_num;
					}
				}
			}
			else
			{
				CLog::warning("pcntl_waitpid() for process exit failed, will not restart it " .
							  "[cur_process_num: %s]", $cur_process_num);
				
				if(WorkerConfig::$mailReport)
				{
					// 日志
				}
			}
		}
		
		if (empty($arr_pid ))
		{
			CLog::trace("all child process not exist");
			break;
		}
		
		sleep(2);
	}
}
 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
