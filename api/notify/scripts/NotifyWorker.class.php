<?php

class NotifyWorker
{
	const TABLE_TASK_INFO_PREFIX = 'task_info_';
	const TABLE_TASK_INFO_PARTITION = 16; // task_info的分表数
	
	const TABLE_PICKRIDE_INFO = 'pickride_info';
	const TABLE_DRIVER_INFO = 'driver_info';
	const TABLE_DEVICE_INFO = 'device_info';
	
	public static function doExecute($cur_process_num, $total_process_num)
	{
		$pre_num = 0;
		$begin_table_num = 0;
		$end_table_num = 0;
		
		// 当分表数大于worker进程数时
		if (self::TABLE_TASK_INFO_PARTITION >= $total_process_num)
		{
			// 每个worker平均要处理的分表数
			$pre_num = intval(self::TABLE_TASK_INFO_PARTITION / $total_process_num);
			
			// 除去平均分配后，剩余的分表数
			$remain_num = self::TABLE_TASK_INFO_PARTITION % $total_process_num;
			
			$begin_table_num = $cur_process_num * $pre_num;
			
			// 若有剩余的分表，则让前$remain_num个worker进程，每个进程多增加1张表的任务处理
			if ($remain_num > 0)
			{
				// 当有剩余分表时，要处理的起始分表需要做调整
				if ($cur_process_num >= $remain_num)
				{
					$begin_table_num += $remain_num;
				}
				else
				{
					$begin_table_num += $cur_process_num;
				}
			}
			
			$end_table_num = $begin_table_num + $pre_num - 1;
			
			// 若有剩余分表，且进程是前$cur_process_num进程，需要多处理一张分表任务
			if ($remain_num > 0 && $cur_process_num < $remain_num)
			{
				$end_table_num += 1;
			}
		}
		else // 当worker进程数大于分表数，这种情况下，每个worker只能处理一张分表
		{
			// 每个分表平均被多少个worker处理
			$pre_num = intval($total_process_num / self::TABLE_TASK_INFO_PARTITION);
			$totoal_average_num = $pre_num * self::TABLE_TASK_INFO_PARTITION;
			
			// 对不能被平分的进程，循环的处理从0开始的每张表
			if ($cur_process_num >= $totoal_average_num)
			{
				$begin_table_num = $cur_process_num % $totoal_average_num;
			}
			else
			{
				$begin_table_num = intval($cur_process_num / ($pre_num));
			}
			
			// 起始表与结束表是同一张表
			$end_table_num = $begin_table_num;
		}
		
		/*
		echo "-------------------------\n";
		echo "cur_process_num: $cur_process_num\n";
		echo "total_process_num: $total_process_num\n";
		echo "pre_num: $pre_num\n";
		echo "begin_table_num: $begin_table_num\n";
		echo "end_table_num: $end_table_num\n";
		echo "-------------------------\n";
		*/
		
		CLog::trace("notify worker start [cur_process_num: %s, begin_table_num: %s, end_table_num: %s, totoal_process_num: %s]",
					$cur_process_num, $begin_table_num, $end_table_num, $total_process_num);
		
		$task_num = 0;
		
		while($task_num < NotifyConfig::MAX_TASK_PROCESS_NUM)
		{
			$table_num = $begin_table_num;
		
			while($table_num <= $end_table_num)
			{
				$ret = self::do_notify($table_num);
				if (NotifyConfig::$arProcessStatus['no_task'] == $ret)
				{
					usleep(NotifyConfig::WORKER_SLEEP_TIME);
				}
				
				++$table_num;
				++$task_num;
			}
		}
	}
	
	public static function do_notify($table_num)
	{
		// 组织分表名
		$table_task_info = self::TABLE_TASK_INFO_PREFIX . $table_num;
		
		//CLog::trace("proc tabl_name: %s", $table_task_info);
		
		$db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
		if (false === $db_proxy)
		{
			CLog::warning("call db failed");
			return;
		}
		
		// 开启事物
		$ret = $db_proxy->startTransaction();
		if (false === $ret)
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		// 拉取创建的任务，并加行锁
		$condition = array(
			'and' => array(
				array(
					'status' => array(
			 			'=' => NotifyConfig::$arrNotifyTaskStatus['new'],
					),
				),
			),
		);
		$append_condition = array(
			'start' => 0, 
			'limit' => 1,
		);
		$arr_response = $db_proxy->selectForUpdate($table_task_info, 
										 		   array('pid', 'user_id', 'phone'), 
										 		   $condition,
										 		   $append_condition);
		if (false === $arr_response || 
			!is_array($arr_response))
		{
			$db_proxy->commit();
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		if (0 == count($arr_response))
		{
			//CLog::trace("no task to do [tabl_name: %s]", $table_task_info);
			$db_proxy->commit();
			return NotifyConfig::$arProcessStatus['no_task'];
		}

		if (!isset($arr_response[0]['pid']))
		{
			$db_proxy->commit();
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		$pid = $arr_response[0]['pid'];
		$user_id = $arr_response[0]['user_id'];
		$phone = $arr_response[0]['phone'];
		
		CLog::trace("get task succ [pid: %s, user_id: %s, phone: %s, task_name: %s]", 
					$pid, $user_id, $phone, $table_task_info);
		
		// 获取任务成功，将任务的状态修改为处理中
		$condition = array(
			'and' => array(
				array(
					'pid' => array(
			 			'=' => $pid,
					),
				),
				array(
					'status' => array(
			 			'=' => NotifyConfig::$arrNotifyTaskStatus['new'],
					),
				),
			),
		);
		$row = array(
			'status' => NotifyConfig::$arrNotifyTaskStatus['processing'],
		);
		$ret = $db_proxy->update($table_task_info, $condition, $row);
		if (false === $ret)
		{
			$db_proxy->commit();
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		// 提交事物
		$ret = $db_proxy->commit();
		if (false === $ret)
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		// 查询pid对应的详细信息
		$condition = array(
			'and' => array(
				array(
					'pid' => array(
			 			'=' => $pid,
					),
				),
			),
		);
		$arr_response = $db_proxy->select(self::TABLE_PICKRIDE_INFO, 
										  array('user_id', 'src', 'dest', 
										  		'src_latitude', 'src_longitude', 
										  		'dest_latitude', 'dest_longitude',
										  		'ctime'), 
										  $condition);
		if (false === $arr_response || !is_array($arr_response))
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}

		if (0 == count($arr_response))
		{
			CLog::warning("the pid does not exist [pid: %s]", $pid);
			self::set_task_status($table_task_info, $pid, 
								  NotifyConfig::$arrNotifyTaskStatus['error_invalid_pid']);
			
			return;
		}
		
		$src = $arr_response[0]['src'];
		$dest = $arr_response[0]['dest'];
		$src_latitude = $arr_response[0]['src_latitude'];
		$src_longitude = $arr_response[0]['src_longitude'];
		$dest_latitude = $arr_response[0]['dest_latitude'];
		$dest_longitude = $arr_response[0]['dest_longitude'];
		$pid_ctime = $arr_response[0]['ctime'];
		
		CLog::debug("get pid info succ [src: %s, dest: %s, " . 
					"src_latitude: %s, src_longitude: %s, " . 
					"dest_latitude: %s, dest_longitude: %s, " .
					"pid_ctime: %s]", 
					$src, $dest, $src_latitude, $src_longitude, 
					$dest_latitude, $dest_longitude, $pid_ctime);
		
		// 确定出发地附近的范围
		$coordinate_object = CoordinateService::getInstance();
		$arr_range = $coordinate_object->get_bound($src_latitude, 
												   $src_longitude, 
												   NotifyConfig::$NotifyRectangleRange);
					
		$src_min_latitude = $arr_range['min_latitude'];
		$src_max_latitude = $arr_range['max_latitude'];
		$src_min_longitude = $arr_range['min_longitude'];
		$src_max_longitude = $arr_range['max_longitude'];
		
		CLog::debug("get range succ [src_min_latitude: %s, src_max_latitude: %s, " . 
					"src_min_longitude: %s, src_max_longitude: %s]",
					$src_min_latitude, $src_max_latitude,
					$src_min_longitude, $src_max_longitude);
					
		// 查询driver_info表
		$expire_time = time() - NotifyConfig::$notifyTimeout;
		$condition = array(
			'and' => array(
				array(
					'latitude' => array(
			 			'>' => $src_min_latitude,
					),
				),
				array(
					'latitude' => array(
			 			'<' => $src_max_latitude,
					),
				),
				array(
					'longitude' => array(
			 			'>' => $src_min_longitude,
					),
				),
				array(
					'longitude' => array(
			 			'<' => $src_max_longitude,
					),
				),
				array(
					'mtime' => array(
			 			'<' => $expire_time,
					),
				),
			),
		);
		$append_condition = array(
			'start' => 0, 
			'limit' => NotifyConfig::DRIVER_LIMIT,
		);
		$arr_response = $db_proxy->select(self::TABLE_DRIVER_INFO, 
										 array('user_id', 'dev_id', 'latitude', 'longitude'), 
										 $condition,
										 $append_condition);
		if (false === $arr_response || !is_array($arr_response))
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}

		// 在范围内，没有找到司机
		if (0 == count($arr_response))
		{
			CLog::warning("no driver around [src_latitude: %s, src_longitude: %s, sql: %s]",
						  $src_latitude, $src_longitude, $db_proxy->getLastSQL());
						  
			self::set_task_status($table_task_info, $pid, 
								  NotifyConfig::$arrNotifyTaskStatus['error_no_driver']);
								  
			return NotifyConfig::$arProcessStatus['no_driver'];
		}

		$driver_num = count($arr_response);
		
		// 组织司机的信息
		$arr_driver_user = array();
		
		// 如果数量较多，则进一步计算两点间距离进行筛选，否则直接进行通知
		$is_skip_count_distance = false;
		if ($driver_num > NotifyConfig::$driverNumLowerBound)
		{
			CLog::trace("the number of drivers is more, will use distance filter");
			
			$is_skip_count_distance = true;
			$pos = 0;
			foreach($arr_response as $info)
			{
				// 计算两点距离
				$distance = $coordinate_object->get_distance($src_latitude,
															 $src_longitude,
														     $info['latitude'], 
														     $info['longitude']);
				if ($distance < NotifyConfig::$NotifyBetweenDistance)
				{
					$arr_driver_user[$pos]['user_id'] = $info['user_id'];
					$arr_driver_user[$pos]['device_id'] = $info['dev_id'];
				}
				
				++$pos;
				CLog::debug("get distance succ [distance: %s, src_latitude: %s, src_longitude: %s, driver_latitude: %s, drvier_longitude: %s]", 
							$distance, $src_latitude, $src_longitude, $info['latitude'], $info['longitude']);
			}
			
			// 若没有满足距离过滤的，则恢复原来的司机数据
			if (0 == count($arr_driver_user))
			{
				CLog::trace("no drivers around after use distance filter");
				$is_skip_count_distance = false;
			}
			else
			{
				CLog::trace("use distance filter succ " .
							"[before_filter_driver_num: %s, " .
							"after_filter_driver_num: %s]",
							$driver_num, count($arr_driver_user));
			}
		}
		
		if (false === $is_skip_count_distance)
		{
			$pos = 0;
			foreach($arr_response as $info)
			{
				$arr_driver_user[$pos]['user_id'] = $info['user_id'];
				$arr_driver_user[$pos]['device_id'] = $info['dev_id'];
				++$pos;
			}
		}
		
		CLog::debug("the driver user_id is [driver_user_id: %s]", json_encode($arr_driver_user));

		/*
		// 查询司机对应的消息推送ID
		$condition = array(
			'and' => array(
				array(
					'user_id' => array(
			 			'in' => $arr_driver_user,
					),
				),
			),
		);
		$append_condition = array(
			'start' => 0, 
			'limit' => count($arr_driver_user),
		);
		$arr_response = $db_proxy->select(self::TABLE_DEVICE_INFO, 
										  array('user_id', 'client_id'), 
										  $condition,
										  $append_condition);
		if (false === $arr_response || 
			!is_array($arr_response))
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		if (0 == count($arr_response))
		{
			CLog::warning("get drvier client_id failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		*/
		
		// 组织消息，开始推送
		$arr_content = array(
			'msg_type' => NotifyConfig::$arrPushType['create_order'],
			'msg_content' => array(
				'phone' => $phone,
				'src' => $src,
				'src_gps' => $src_latitude . ',' . $src_longitude,
				'dest' => $dest,
				'dest_gps' => $dest_latitude . ',' . $dest_longitude,
				'pid' => $pid,
				'ctime' => $pid_ctime,
				'timeout' => CarpoolConfig::CARPOOL_ORDER_TIMEOUT,
			),
			'msg_ctime' => time(),
			'msg_expire' => NotifyConfig::$notifyMsgTimeout,
		);
		$arr_msg = array(
			'trans_type' => 1,
			'trans_content' => json_encode($arr_content),
		);
		$user_type = 1; // 司机类型;
		
		$push_proxy_object = PushPorxy::getInstance();
		
		$ret = $push_proxy_object->push_to_list(PushProxyConfig::$arrPushMsgType['trans'], 
							  	 		 		$arr_msg, 
							  	 		 		$arr_driver_user, 
							  	 		 		$user_type,
							  	 		 		false);
		if (false === $ret)
		{
			CLog::warning("notify failed [pid: %s]", $pid);
			
			self::set_task_status($table_task_info, $pid, 
								  NotifyConfig::$arrNotifyTaskStatus['error_push']);
			return;
		}
		
		CLog::trace("notify succ [pid: %s, driver_num: %s]", $pid, count($arr_driver_user));
	}
	
	public static function set_task_status($table_name, $pid, $status)
	{
		$db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
		if (false === $db_proxy)
		{
			CLog::warning("call db failed");
			return;
		}
		
		// 获取任务成功，将任务的状态修改为处理中
		$condition = array(
			'and' => array(
				array(
					'pid' => array(
			 			'=' => $pid,
					),
				),
			),
		);
		$row = array(
			'status' => $status,
		);
		$ret = $db_proxy->update($table_name, $condition, $row);
		if (false === $ret)
		{
			CLog::warning("call db failed [sql: %s]", $db_proxy->getLastSQL());
			return;
		}
		
		CLog::debug("set_task_status succ [table_name: %s, pid: %s, status: %s]",
					$table_name, $pid, $status);
	}
	
	public static function _test_push()
	{
		$push_proxy_object = PushPorxy::getInstance();

/*
		$user_type = 2;
		$arr_user = array(
			array(
				'user_id' => 666,
				'device_id' => '800',
			),
		);
		
		
		$arr_content = array(
			'msg_type' => 1,
			'msg_content' => array(
				'user_id' => 123,
				'src' => '百度大厦',
				'src_gps' => '123,456',
				'dest' => '三元桥',
				'dest_gps' => '776,656',
				'pid' => 4456,
			),
			'msg_ctime' => 111,
			'msg_expire' => 444,
		);
		$arr_msg = array(
			'trans_type' => 1,
			'trans_content' => json_encode($arr_content),
		);
		$push_proxy_object->push_to_single(PushProxyConfig::$arrPushMsgType['trans'], 
								   		   $arr_msg, 
								   		   $arr_user,
								   		   $user_type);

*/
				
		/*
		$arr_msg = array(
			'title' => '拼车通知',
			'text'  => '拼车内容',
			'logo'  => 'xxx.png',
			'trans_type' => 1,
			'trans_content' => 'fire in the hole',
			'is_ring'       => true,
			'is_vibrate'    => true,
		);
		$push_proxy_object->push_to_single(PushProxyConfig::$arrPushMsgType['notify'], 
								   		   $arr_msg, 
								   		   $user_id);
		*/
	

		$arr_msg = array(
			'title' => '拼车通知',
			'text'  => '拼车内容',
			'logo'  => 'xxx.png',
			'trans_type' => 1,
			'trans_content' => 'fire in the hole',
			'is_ring'       => true,
			'is_vibrate'    => true,
		);
		$arr_user = array(
			array(
				'user_id' => 666,
				'device_id' => '800',
			),
			array(
				'user_id' => 667,
				'device_id' => '801',
			),
			array(
				'user_id' => 668,
				'device_id' => '802',
			),
			array(
				'user_id' => 669,
				'device_id' => '803',
			),
		);
		$user_type = 2;
		
		$push_proxy_object->push_to_list(PushProxyConfig::$arrPushMsgType['notify'], 
							  	 		 $arr_msg, 
							  	 		 $arr_user,
							  	 		 $user_type);

	}
	
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
