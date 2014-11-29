<?php

class PushPorxy
{
	private static $instance = NULL;
	
	private $igt_obj = NULL;
	
	const TABLE_DEVICE_INFO = 'device_info';
	
	/**
	 * @return PushPorxy
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new PushPorxy();
		}
		
		return self::$instance;
	}

	protected function __construct()
	{
		$this->igt_obj = new IGeTui(PushProxyConfig::$host,
									PushProxyConfig::$appKey,
									PushProxyConfig::$mastersecret);
									
		$this->igt_obj->set_debug(PushProxyConfig::$pushDebugMode);
	}
	
	/*
	 * 推送消息给单个client
	 * @param  int	  $msg_type; 参考PushProxyConfig::$arrPushMsgType
	 * @param  array  $arr_msg; $msg_type决定消息内容，参考PushProxyConfig::$arrPushMsgNotify等
	 * @param  string $user_id;
	 * @param  bool   $is_offline; 是否离线
	 * @param  int    $expire; 离线时的超时时间
	 * @return array  
	 */
	public function push_to_single($msg_type, 
								   $arr_msg, 
								   $user_id, 
								   $is_offline = true, 
								   $expire = PushProxyConfig::PUSH_OFFLINE_EXPIRE_TIME)
	{
		$template = $this->_gene_msg($msg_type, $arr_msg);
		if (NULL == $template)
		{
			return false;
		}
		
		// 组织个推的message
		$message = new IGtSingleMessage();
		$message->set_data($template);
		
		if (true === $is_offline)
		{
			$message->set_isOffline(true);
			$message->set_offlineExpireTime($expire * 1000); // 转换成毫秒
		}
		else
		{
			$message->set_isOffline(false);
		}
		
		// 查询user_id对应的push_id(即:client_id)
		$user_client_id = $this->_get_client_id(array($user_id));
		if (false === $user_client_id)
		{
			return false;
		}
		
		if (true === PushProxyConfig::$pushDebugMode)
		{
			CLog::debug("get user_client_id succ [user_client_id: %s]",
						json_encode($user_client_id));
		}
		
		$client_id = $user_client_id[0]['client_id'];

		// 组织个推的target
		$target = new IGtTarget();
        
		$target->set_appId(PushProxyConfig::$appid);
		$target->set_clientId($client_id);
		
		// 调用个推接口，推送消息
		$arr_ret = $this->igt_obj->pushMessageToSingle($message,$target);
		if (!isset($arr_ret['result']) ||
			'ok' !== $arr_ret['result'])
		{
			CLog::warning("push_to_single failed [result: %s]", $arr_ret['result']);
			return false;
		}
		
		return true;
	}
	
	/*
	 * 推送消息给多个client
	 * @param  int	  $msg_type; 参考PushProxyConfig::$arrPushMsgType
	 * @param  array  $arr_msg; $msg_type决定消息内容，参考PushProxyConfig::$arrPushMsgNotify等
	 * @param  array  $arr_user_id; 多个user_id的数组
	 * @param  bool   $is_offline; 是否离线
	 * @param  int    $expire; 离线时的超时时间
	 * @return array  
	 */
	public function push_to_list($msg_type, 
							  	 $arr_msg, 
							  	 $arr_user_id, 
							  	 $is_offline = true, 
							  	 $expire = PushProxyConfig::PUSH_OFFLINE_EXPIRE_TIME)
	{
		$template = $this->_gene_msg($msg_type, $arr_msg);
		if (NULL == $template)
		{
			return false;
		}

		// 组织个推的message
		$message = new IGtSingleMessage();
		$message->set_data($template);
		
		if (true === $is_offline)
		{
			$message->set_isOffline(true);
			$message->set_offlineExpireTime($expire * 1000); // 转换成毫秒
		}
		else
		{
			$message->set_isOffline(false);
			
			// 个推的bug，即使是非线下推送，也需要设置超时时间，囧
			$message->set_offlineExpireTime($expire * 1000);
		}
		
		$content_id = $this->igt_obj->getContentId($message);
		if (empty($content_id))
		{
			CLog::warning("get content_id failed");
			return false;
		}
		
		// 查询user
		$arr_user_client_id = $this->_get_client_id($arr_user_id);
		if (false === $arr_user_client_id)
		{
			return false;
		}
		
		if (true === PushProxyConfig::$pushDebugMode)
		{
			CLog::debug("get user_client_id succ [user_client_id: %s]",
						json_encode($arr_user_client_id));
		}
		
		// 组织个推的target
		$arr_target = array();
		foreach ($arr_user_client_id as $client_id)
		{
			$target = new IGtTarget();
			$target->set_appId(PushProxyConfig::$appid);
			$target->set_clientId($client_id['client_id']);
			
			$arr_target[] = $target;
		}
		
		// 调用个推接口，推送消息
		$arr_ret = $this->igt_obj->pushMessageToList($content_id, $arr_target);
		if (!isset($arr_ret['result']) ||
			'ok' !== $arr_ret['result'])
		{
			CLog::warning("push_to_list failed [result: %s]", $arr_ret['result']);
			return false;
		}
		
		return true;
	}
	
	/*
	 * 推送消息至群体，可按appid、地域、手机类型(android/ios)、标签等划分群体
	 * @param  int	  $msg_type; 参考PushProxyConfig::$arrPushMsgType
	 * @param  array  $arr_msg; $msg_type决定消息内容，参考PushProxyConfig::$arrPushMsgNotify等
	 * @param  int    $group_type; 参考PushProxyConfig::$arrPushGroupType
	 * @param  array  $arr_group; 参考$arrPushGroupType的注释
	 * @param  bool   $is_offline; 是否离线
	 * @param  int    $expire; 离线时的超时时间
	 * @return array  
	 */
	public function push_to_group($msg_type, 
						  	 	  $arr_msg, 
						  	 	  $group_type,
						  	 	  $arr_group, 
						  	 	  $is_offline = true, 
						  	      $expire = PushProxyConfig::PUSH_OFFLINE_EXPIRE_TIME)
	{
		$template = $this->_gene_msg($msg_type, $arr_msg);
		if (NULL == $template)
		{
			return false;
		}
		
		// 组织个推的message
		$message = new IGtAppMessage();
		$message->set_data($template);
		
		if (true === $is_offline)
		{
			$message->set_isOffline(true);
			$message->set_offlineExpireTime($expire * 1000); // 转换成毫秒
		}
		else
		{
			$message->set_isOffline(false);
		}

		// 组织group
		switch ($group_type)
		{
			case PushProxyConfig::$arrPushGroupType['app']:
			{
				$message->set_appIdList($arr_group);
				break;
			}
			
			case PushProxyConfig::$arrPushGroupType['ctype']:
			{
				$message->set_phoneTypeList($arr_group);
				break;
			}
			
			case PushProxyConfig::$arrPushGroupType['province']:
			{
				$message->set_provinceList($arr_group);
				break;
			}
			
			case PushProxyConfig::$arrPushGroupType['tag']:
			{
				$message->set_tagList($arr_group);
				break;
			}
		}
		
		// 调用个推接口，推送消息
		$ret = $this->igt_obj->pushMessageToApp($message);
		// todo 判断ret
		
		return true;
	}

	
	private function _gene_msg($msg_type, $arr_msg)
	{
		$msg_template = NULL;
		switch ($msg_type)
		{
			case PushProxyConfig::$arrPushMsgType['notify']:
			{
				$msg_template = $this->_gene_notify_msg($arr_msg);
				break;		
			}
			
			case PushProxyConfig::$arrPushMsgType['link']:
			{
				$msg_template = $this->_gene_link_msg($arr_msg);
				break;		
			}
			
			case PushProxyConfig::$arrPushMsgType['popload']:
			{
				$msg_template = $this->_gene_popload_msg($arr_msg);
				break;		
			}
			
			case PushProxyConfig::$arrPushMsgType['trans']:
			{
				$msg_template = $this->_gene_trans_msg($arr_msg);
				break;		
			}
			
			default:
				CLog::warning("invalid msg_type [msg_type: %s]", $msg_type);
				return NULL;
		}
		
        $msg_template->set_appId(PushProxyConfig::$appid);
        $msg_template->set_appkey(PushProxyConfig::$appKey);
        
		return $msg_template;
	}
	
	private function _gene_notify_msg($arr_msg)
	{
		$debug_msg_str = NULL;
		
		// 检查必选参数
		if (!isset($arr_msg['title']) ||
			!isset($arr_msg['text']) ||
			!isset($arr_msg['logo']) ||
			!isset($arr_msg['trans_type']) ||
			!isset($arr_msg['trans_content']))
		{
			return false;
		}
		
		$debug_msg_str .= 'title: ' . $arr_msg['title'] . ', ';
		$debug_msg_str .= 'text: ' 	. $arr_msg['text'] 	. ', ';
		$debug_msg_str .= 'logo: ' 	. $arr_msg['logo'] 	. ', ';
		$debug_msg_str .= 'trans_type: ' 	. $arr_msg['trans_type'] . ', ';
		$debug_msg_str .= 'trans_content: ' . $arr_msg['trans_content'];
		
		$msg_template = new IGtNotificationTemplate();

        $msg_template->set_appId(PushProxyConfig::$appid);
        $msg_template->set_appkey(PushProxyConfig::$appKey);
        
        $msg_template->set_title($arr_msg['title']);
        $msg_template->set_text($arr_msg['text']);
        $msg_template->set_logo($arr_msg['logo']);
        
        $msg_template->set_transmissionType($arr_msg['trans_type']);
        $msg_template->set_transmissionContent($arr_msg['trans_content']);
		
        if (isset($arr_msg['is_ring']))
        {
        	$msg_template->set_isRing(true);
        	$debug_msg_str .= ', is_ring: ' . $arr_msg['is_ring'];
        }
        
	    if (isset($arr_msg['is_vibrate']))
        {
        	$msg_template->set_isVibrate(true);
        	$debug_msg_str .= ', is_vibrate: ' . $arr_msg['is_vibrate'];
        }
        
	    if (isset($arr_msg['is_clearable']))
        {
        	$msg_template->set_isClearable(true);
        	$debug_msg_str .= ', is_clearable: ' . $arr_msg['is_clearable'];
        }
        
        CLog::debug("generate notify msg succ [%s]", $debug_msg_str);
        
        return $msg_template;
	}
	
	private function _gene_link_msg($arr_msg)
	{
		$debug_msg_str = NULL;
		
		// 检查必选参数
		if (!isset($arr_msg['title']) ||
			!isset($arr_msg['text']) ||
			!isset($arr_msg['logo']) ||
			!isset($arr_msg['url']))
		{
			return false;
		}
		
		$debug_msg_str .= 'title: ' . $arr_msg['title'] . ', ';
		$debug_msg_str .= 'text: ' 	. $arr_msg['text'] 	. ', ';
		$debug_msg_str .= 'logo: ' 	. $arr_msg['logo'] 	. ', ';
		$debug_msg_str .= 'url: ' 	. $arr_msg['url'] 	. ', ';
		
		$msg_template = new IGtLinkTemplate();

        $msg_template->set_appId(PushProxyConfig::$appid);
        $msg_template->set_appkey(PushProxyConfig::$appKey);
        
        $msg_template->set_title($arr_msg['title']);
        $msg_template->set_text($arr_msg['text']);
        $msg_template->set_logo($arr_msg['logo']);
        
        $msg_template->set_url($arr_msg['url']);
		
        if (isset($arr_msg['is_ring']))
        {
        	$msg_template->set_isRing(true);
        	$debug_msg_str .= ', is_ring: ' . $arr_msg['is_ring'];
        }
        
	    if (isset($arr_msg['is_vibrate']))
        {
        	$msg_template->set_isVibrate(true);
        	$debug_msg_str .= ', is_vibrate: ' . $arr_msg['is_vibrate'];
        }
        
	    if (isset($arr_msg['is_clearable']))
        {
        	$msg_template->set_isClearable(true);
        	$debug_msg_str .= ', is_clearable: ' . $arr_msg['is_clearable'];
        }
        
        CLog::debug("generate link msg succ [%s]", $debug_msg_str);
        
        return $msg_template;
	}
	
	private function _gene_popload_msg($arr_msg)
	{
		// todo
	}
	
	private function _gene_trans_msg($arr_msg)
	{
		$debug_msg_str = NULL;
		
		// 检查必选参数
		if (!isset($arr_msg['trans_type']) ||
			!isset($arr_msg['trans_content']))
		{
			return false;
		}
		
		$debug_msg_str .= 'trans_type: ' 	. $arr_msg['trans_type'] . ', ';
		$debug_msg_str .= 'trans_content: ' . $arr_msg['trans_content'];
		
		$msg_template = new IGtTransmissionTemplate();
        
        $msg_template->set_transmissionType($arr_msg['trans_type']);
        $msg_template->set_transmissionContent($arr_msg['trans_content']);
        
        CLog::debug("generate trans msg succ [%s]", $debug_msg_str);
        
        return $msg_template;
	}
	
	private function _get_client_id($arr_user_id)
	{
		$dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
		if (false === $dbProxy)
		{
			CLog::warning("connect to the DB failed");
			return false;
		}
		
		/*
		$in_option = '';
		foreach($arr_user_id as $user_id)
		{
			$in_option .= $user_id . ',';
		}
		
		// 去掉最后一个逗号
		$in_option = substr(string, 0, strlen($in_option) - 1);
		*/
		
		$condition = array(
			'and' => array(
				array(
					'user_id' => array(
			 			'in' => $arr_user_id,
					),
				),
			),
		);
		$append_condition = array(
			'start' => 0,
			'limit' => count($arr_user_id),
		);
		$arr_response = $dbProxy->select(self::TABLE_DEVICE_INFO, 
										 array('user_id', 'client_id'), 
										 $condition,
										 $append_condition);
		if (false === $arr_response || !is_array($arr_response))
		{
			CLog::warning("select from the DB failed");
			return false;
		}
		
		return $arr_response;
		
	}
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
