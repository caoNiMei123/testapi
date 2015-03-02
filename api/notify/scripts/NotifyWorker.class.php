<?php

class NotifyWorker
{
    const TABLE_DRIVER_INFO = 'driver_info';
    const TABLE_DEVICE_INFO = 'device_info';
    
    // 拼车订单状态
    const CARPOOL_STATUS_CREATE  = 0;
    const CARPOOL_STATUS_ACCEPTED  = 1;
    const CARPOOL_STATUS_ABOARD  = 2;
    const CARPOOL_STATUS_CANCLED  = 3;    
    const CARPOOL_STATUS_DONE = 4;
    const CARPOOL_STATUS_TIMEOUT  = 5;
    
    public static function doExecute($arr_task_info)
    {
        $pid = $arr_task_info['pid'];
        $user_id = $arr_task_info['user_id'];
        $phone = $arr_task_info['phone'];
        $name = $arr_task_info['name'];
        $head = $arr_task_info['head'];
        $sex = $arr_task_info['sex'];
        $pid_ctime = $arr_task_info['ctime'];
        $timeout = $arr_task_info['timeout'];
        $price = $arr_task_info['price'];
        $mileage = $arr_task_info['mileage'];
        
        $src = $arr_task_info['src'];
        $dest = $arr_task_info['dest'];
        
        $src_gps = $arr_task_info['src_gps'];
        $dest_gps = $arr_task_info['dest_gps'];
        
        $arr_ret = explode(',', $src_gps);
        if (false === $arr_ret || 0 == count($arr_ret))
        {
            CLog::warning("invalid src_gps [src_gps: %s]", $src_gps);
            return;
        }
        
        $src_latitude = $arr_ret[0];
        $src_longitude = $arr_ret[1];
        
        $arr_ret = explode(',', $dest_gps);
        if (false === $arr_ret || 0 == count($arr_ret))
        {
            CLog::warning("invalid dest_gps [dest_gps: %s]", $dest_gps);
            return;
        }
        
        $dest_latitude = $arr_ret[0];
        $dest_longitude = $arr_ret[1];
        
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
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            CLog::warning("call db failed");
            return;
        }
        
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
                        '>' => $expire_time,
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
        
        CLog::debug("get driver succ [sql: %s]", $db_proxy->getLastSQL());
        
        // 在范围内，没有找到司机
        if (0 == count($arr_response))
        {
            CLog::warning("no driver around [src_latitude: %s, src_longitude: %s, sql: %s]",
                          $src_latitude, $src_longitude, $db_proxy->getLastSQL());
                                  
            return;
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

        
        // 组织消息，开始推送
        $arr_content = array(
            'msg_type' => NotifyConfig::$arrPushType['create_order'],
            'msg_content' => array(
                'phone' => $phone,
                'name' => $name,
                'sex' => $sex,
                'head' => $head,
                'src' => $src,
                'src_gps' => $src_latitude . ',' . $src_longitude,
                'dest' => $dest,
                'dest_gps' => $dest_latitude . ',' . $dest_longitude,
                'pid' => $pid,
                'ctime' => $pid_ctime,
                'price' => $price,
                'mileage' => $mileage, 
                'timeout' => CarpoolConfig::CARPOOL_ORDER_TIMEOUT,
            ),
            'msg_ctime' => time(),
            'msg_expire' => $timeout,
        );
        $arr_msg = array(
            'trans_type' => 2,
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
            return;
        }
        
        CLog::trace("notify succ [pid: %s, driver_num: %s]", $pid, count($arr_driver_user));
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
            'trans_type' => 2,
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
            'trans_type' => 2,
            'trans_content' => 'fire in the hole',
            'is_ring'       => true,
            'is_vibrate'    => true,
        );
        $push_proxy_object->push_to_single(PushProxyConfig::$arrPushMsgType['notify'], 
                                           $arr_msg, 
                                           $user_id);
        */
    
        /*
        $arr_msg = array(
            'title' => '拼车通知',
            'text'  => '拼车内容',
            'logo'  => 'xxx.png',
            'trans_type' => 2,
            'trans_content' => 'fire in the hole',
            'is_ring'       => true,
            'is_vibrate'    => true,
        );
        $arr_user = array(
            array(
                'user_id' => 10001,
                'device_id' => '1',
            ),
            array(
                'user_id' => 10003,
                'device_id' => '864645022465485',
            ),
            array(
                'user_id' => 10007,
                'device_id' => 'A00000405774D7',
            ),
        );
        $user_type = 1;
        
        $push_proxy_object->push_to_list(PushProxyConfig::$arrPushMsgType['notify'], 
                                         $arr_msg, 
                                         $arr_user,
                                         $user_type);
        */
        /*
        $arr_msg = array(
            'trans_type' => 2,
            'trans_content' => 'helleo hahah',
        );
        
        $user_type = 1;
        $arr_user = array(
            array(
                'user_id' => 10001,
                'device_id' => '1',
            ),
            array(
                'user_id' => 10003,
                'device_id' => '864645022465485',
            ),
            array(
                'user_id' => 10007,
                'device_id' => 'A00000405774D7',
            ),
        );
        $push_proxy_object->push_to_list(PushProxyConfig::$arrPushMsgType['trans'], 
                                         $arr_msg, 
                                         $arr_user,
                                         $user_type);
        */
    }
    
}

 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
