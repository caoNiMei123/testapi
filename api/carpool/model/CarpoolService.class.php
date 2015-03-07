<?php

class CarpoolService
{
    const CARPOOL_STATUS_CREATE  = 0;
    const CARPOOL_STATUS_ACCEPTED  = 1;
    const CARPOOL_STATUS_ABOARD  = 2;
    const CARPOOL_STATUS_CANCLED  = 3;    
    const CARPOOL_STATUS_DONE = 4;
    const CARPOOL_STATUS_TIMEOUT  = 5;

    

    private static $instance = NULL;
    
    /**
     * @return StreamService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new CarpoolService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {

    }

    public function create($arr_req, $arr_opt)
    {
        $devuid = $arr_req['devuid'] ;
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $src = $arr_req['src'] ;
        $dest = $arr_req['dest'] ;
        $src_gps = $arr_req['src_gps'] ;
        $dest_gps = $arr_req['dest_gps'] ; 
        $mileage = $arr_req['mileage'] ; 

        $detail = isset($arr_opt['detail'])?$arr_opt['detail']:'';
        
        Utils::check_string($src, 1, 256); 
        
        Utils::check_string($dest, 1, 256); 
        
        Utils::check_string($src_gps, 1, 256); 
        
        Utils::check_string($dest_gps, 1, 256); 

        Utils::check_string($detail, 0, 256); 
        
        $src_gps_arr = explode(',', $src_gps);
        $dest_gps_arr = explode(',', $dest_gps);
        if(!is_array($src_gps_arr) || !is_array($dest_gps_arr) || count($src_gps_arr) !=2 || count($dest_gps_arr) !=2) 
        {
            throw new Exception('carpool.param invalid gps');
        }

        
        $row = array();
        $now = time(NULL);
        
        // 使用63位无符号签名，避免php数字溢出
        $pid = Utils::sign63(time(NULL).$user_id.$src_gps.$dest_gps.rand());

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);

        $ret = $db_proxy->startTransaction();
        if (false === $ret)
        {
            throw new Exception('carpool.internal start transaction fail');
        }

        $arr_response = $db_proxy->selectForUpdate('pickride_info', array('pid'),array('and'=>
            array(array('user_id' =>  array('=' => $user_id)),  
            array('ctime' =>  array('>' => ($now - CarpoolConfig::CARPOOL_ORDER_TIMEOUT))), 
            array('status' =>  array('>' => -1)), 
            array('status' =>  array('<' => self::CARPOOL_STATUS_CANCLED)),    // 完成需要在取消状态之后定义           
        )));      

        if (false === $arr_response || !is_array($arr_response))
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal select from the DB failed');
        }

        if (0 != count($arr_response)) {
            $db_proxy->rollback();
            throw new Exception('carpool.duplicate has another carpool doing');
        }   

        $row = array(
            'pid' => $pid,
            'user_id'       => $user_id,
            'passenger_dev_id'      => $devuid,            
            'phone'       => intval($user_name),
            'src'           => $src,
            'dest'          => $dest,
            'src_longitude' => $src_gps_arr[1],
            'src_latitude'  => $src_gps_arr[0], 
            'dest_longitude'=> $dest_gps_arr[1],                
            'dest_latitude' => $dest_gps_arr[0],                                        
            'ctime'         => $now,
            'mtime'         => $now,
            'mileage'       => $mileage,
            'detail'          => $detail,
        ); 
        
        //$db_proxy->startTransaction();
        $ret = $db_proxy->insert('pickride_info', $row);
        if (false === $ret)
        {
            $db_proxy->rollback();
            if($db_proxy->getErrorCode() == 1062)
            {
                throw new Exception('carpool.duplicate pid alrealdy created');
            }
            else    
            {
                throw new Exception('carpool.internal insert to the DB failed [err_code: ]' .
                                    $db_proxy->getErrorCode() . ' err_msg: ' . $db_proxy->getErrorMsg());
            }
        }       
        $db_proxy->commit();
        $arr_response = array(
            'pid' => $pid,  
            'timeout' =>CarpoolConfig::CARPOOL_ORDER_TIMEOUT,  
            'price' => CarpoolConfig::ORDER_PRICE_NORMAL * $mileage / 1000,      
        );
        

        //查用户的性别，昵称信息    
        $name = '';
        $sex = 0;
        $arr_user_info = $db_proxy->select('user_info', array('user_id', 'name', 'sex', 'status'), array(
            'and' => array(array('user_id' => array('=' => $user_id,),),),));
        if (false !== $arr_user_info && is_array($arr_user_info) && 1 == count($arr_user_info))
        {
            $name = $arr_user_info[0]['name'];
            $sex = intval($arr_user_info[0]['sex']);
        }



        //抛起一个异步任务, 写task表

        $task = json_encode(
            array(
                'pid' => $pid,
                'user_id'       => $user_id,
                'head'          => CarpoolConfig::$domain."/rest/2.0/carpool/image?method=thumbnail&ctype=1&devuid=1&uk=$uk&timestamp=$now&sign=".hash_hmac('sha1', "$uk:$now", CarpoolConfig::$s3SK, false),
                'phone'         => $user_name,   
                'name'          => $name,
                'sex'           => $sex,                                    
                'ctime'         => $now,
                'mtime'         => $now,
                'price'         => CarpoolConfig::ORDER_PRICE_NORMAL * $mileage / 1000,  
                'mileage'       => $mileage, 
                'src'           => $src,
                'dest'          => $dest,
                'src_gps'       => $arr_req['src_gps'],
                'dest_gps'      => $arr_req['dest_gps'],     
                'timeout'       =>CarpoolConfig::CARPOOL_ORDER_TIMEOUT,      
            )    
        ); 
        
        $php_ipc = new PHPIpcSender(IPCConfig::$domain_info);

        $ret = $php_ipc->call($task, Clog::logId());
        if(false === $ret)
        {
            CLog::fatal("call ipc failed pid : %d", $pid);
        }

        CLog::debug("push task to worker : $task");

        CLog::trace("order create succ [account: %s, user_id : %d, pid : %s]", $user_name, $user_id, $pid);
        return $arr_response;
    }   

    public function getPrice($arr_req, $arr_opt)
    {
        return array(
            'price' => CarpoolConfig::ORDER_PRICE_NORMAL * $arr_req['mileage'] / 1000,
        );

    }

    public function cancel($arr_req, $arr_opt)
    {
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $devuid = $arr_req['devuid'];
        $pid = $arr_req['pid'] ;
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);

        $ret = $db_proxy->startTransaction();
        if (false === $ret)
        {
            throw new Exception('carpool.internal start transaction fail');
        }

        $arr_response = $db_proxy->selectForUpdate('pickride_info', array('status', 'pid', 'user_id', 'driver_id', 'phone', 'driver_phone', 'passenger_dev_id', 'driver_dev_id'),array('and'=>           
            array(array('pid' =>  array('=' => $pid)),                          
        )));
        if (false === $arr_response || !is_array($arr_response))
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.not_found pid not exist');
        }
        $status = $arr_response[0]['status'];
        $ret = false;
        $to_uid = 0;
        $to_phone = 0; 
        $to_devuid = '';
        if ($user_id == intval($arr_response[0]['user_id'])) 
        {
            //乘客逻辑 
            if ($status != self::CARPOOL_STATUS_CREATE && $status != self::CARPOOL_STATUS_ACCEPTED) 
            {
                throw new Exception('carpool.order_status status not allowed');
            }   
            $ret = $db_proxy->update('pickride_info', array('and'=>
                array(array('pid' => array('=' => $pid)),
                    array('user_id' =>  array('=' => $user_id)),                                  
            )), 'mtime ='.time(NULL).',status='.self::CARPOOL_STATUS_CANCLED); 
            $to_uid = intval($arr_response[0]['driver_id']);  
            $to_phone = $arr_response[0]['phone'];  
            $to_devuid = $arr_response[0]['driver_dev_id']; 
                
        }
        else 
        {
            //司机逻辑        

            //如果也不是司机 ，直接拒绝
            if($user_id != intval($arr_response[0]['driver_id']))
            {
                throw new Exception('carpool.param status not allowed');
            }
                 
            if ($status != self::CARPOOL_STATUS_ACCEPTED) 
            {
                throw new Exception('carpool.order_status status not allowed');
            }  
            $ret = $db_proxy->update('pickride_info', array('and'=>
                array(array('pid' => array('=' => $pid)),
                    array('driver_id' =>  array('=' => $user_id)),                                   
            )), 'mtime ='.time(NULL).',status='.self::CARPOOL_STATUS_CANCLED);  
            $to_uid = intval($arr_response[0]['user_id']);
            $to_phone = $arr_response[0]['driver_phone'];
            $to_devuid = $arr_response[0]['passenger_dev_id']; 
        }
           
        if (false === $ret) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.not_found this pid not exists');
        }
        $db_proxy->commit();
        // 订单已被司机接单，才需要发通知到司机
        if (0 != $to_uid)
        {
            $msg = json_encode(array(
                'msg_type' => CarpoolConfig::$arrPushType['cancel_order'],
                'msg_content' => array(
                    'pid' => $pid,    
                    'phone' => $to_phone,               
                ),
                'msg_ctime' => time(NULL),
                'msg_expire' => 60,
            ));
            
            $arr_msg = array(
                'trans_type' => 2,
                'trans_content' => $msg,
            );
            $arr_user = array(
                array(
                    'user_id' => $to_uid,
                    'device_id' => $to_devuid,
                ),
            );
            
            PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user);
        }       

        CLog::trace("order cancel succ [account: %s, user_id : %d, pid : %s]", $user_name, $user_id, $pid);

        return true;
    }   



    public function accept($arr_req, $arr_opt)
    {               
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;        
        $pid = $arr_req['pid'];
        $devuid = $arr_req['devuid'];
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);

        //先检查司机有没有资格接单

        $condition = array(
            'and' => array(
                array(
                    'user_id' => array(
                        '=' => $user_id,
                    ),
                    'status' => array(
                        '=' => UserService::USERSTATUS_AUTHORIZED,
                    ),
                ),             

            ),
        );
        
        $arr_response = $db_proxy->select('user_info', array('user_id', 'name', 'sex', 'car_num', 'car_engine_num', 'car_type'), $condition);
        if (false === $arr_response || !is_array($arr_response) || 0 == count($arr_response))
        {
            throw new Exception('carpool.invalid_driver not a driver');
        }

        $name = $arr_response[0]['name'];
        $sex = $arr_response[0]['sex'];
        $car_num = $arr_response[0]['car_num'];
        $car_engine_num = $arr_response[0]['car_engine_num'];
        $car_type = $arr_response[0]['car_type'];

        //查询订单状态
        $arr_response = $db_proxy->select('pickride_info', '*',array('and'=>           
            array(array('pid' => array('=' => $pid)),
                array('status' =>  array('=' => self::CARPOOL_STATUS_CREATE)), 
                array('ctime' =>  array('>' => $now - CarpoolConfig::CARPOOL_ORDER_TIMEOUT)),              
        )));        
        if (false === $arr_response || !is_array($arr_response))
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) 
        {
            throw new Exception('carpool.param pid not exist');
        }

        $passenger_id = intval($arr_response[0]['user_id']);
        $passenger_phone = intval($arr_response[0]['phone']);
        $passenger_dev_id = $arr_response[0]['passenger_dev_id'];   

        $arr_response = $db_proxy->select('driver_info', array('latitude', 'longitude'),array('and'=>   
            array(array('user_id' =>  array('=' => $user_id)), 
            array('status' =>  array('=' => 0)),
            //array('mtime' =>  array('>' => time(NULL) - CarpoolConfig::CARPOOL_DRIVER_REPORT_TIMEOUT)), 
        )));  

        if (false === $arr_response || !is_array($arr_response) || 0 == count($arr_response))
        {
            throw new Exception('carpool.invalid_driver not a driver');
        }

        $latitude = $arr_response[0]['latitude'];
        $longitude = $arr_response[0]['longitude'];      

        //开事务处理订单流程
        $ret = $db_proxy->startTransaction();
        if (false === $ret)
        {
            throw new Exception('carpool.internal start transaction fail');
        }
        $now =time(NULL);
      
        $arr_response = $db_proxy->selectForUpdate('pickride_info', array('mileage', 'pid', 'passenger_dev_id'),array('and'=>           
            array(array('driver_id' =>  array('=' => $user_id)), 
            array('status' =>  array('=' => self::CARPOOL_STATUS_ACCEPTED)),                          
        )));        
        if (false === $arr_response || !is_array($arr_response))
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 != count($arr_response)) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.duplicate another pid doing');
        }

        $mileage = intval($arr_response[0]['mileage']);
        
        $ret = $db_proxy->update('pickride_info', array('and'=>
            array(array('pid' => array('=' => $pid)),
                array('user_id' =>  array('!=' => $user_id)),  
                array('status' =>  array('=' => self::CARPOOL_STATUS_CREATE)), 
                array('ctime' =>  array('>' => $now - CarpoolConfig::CARPOOL_ORDER_TIMEOUT)),              
        )), 'driver_id='.$user_id.',driver_phone = '.intval($user_name).',mtime ='.time(NULL).',status ='.self::CARPOOL_STATUS_ACCEPTED. ", driver_dev_id = '$devuid'");
        if (false === $ret) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.not_found this pid not exists');
        }
        $db_proxy->commit();
        $msg = json_encode(array(
            'msg_type' => CarpoolConfig::$arrPushType['accept_order'],
            'msg_content' => array(
                'pid' => $pid,
                'phone' => $user_name,
                'uk'=> UserService::api_encode_uid($user_id),
                'gps'=>"$latitude,$longitude",
                'name' =>$name,
                'sex' =>$sex,
                'car_num' =>$car_num,
                'car_engine_num' =>$car_engine_num,
                'car_type' =>$car_type,
            ),
            'msg_ctime' => time(NULL),
            'msg_expire' => 60,
        ));
        
        $arr_msg = array(
            'trans_type' => 2,
            'trans_content' => $msg,
        );
        $arr_user = array(
            array(
                'user_id' => $passenger_id,
                'device_id' => $passenger_dev_id,
            ),
        );
        
        //通知 乘客我已经接单
        PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user);
        
        CLog::trace("order accept succ [account: %s, user_id : %d, pid : %d, passenger: %d ]", 
                    $user_name, $user_id, $pid, $passenger_phone);
        
        return array(
            'price' => CarpoolConfig::ORDER_PRICE_NORMAL * $mileage / 1000,
        );
    }   

    public function finish($arr_req, $arr_opt)
    {               
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;        
        $pid = $arr_req['pid'] ;
        $devuid = $arr_req['devuid'];      
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        //开事务处理订单流程
        $ret = $db_proxy->startTransaction();
        if (false === $ret)
        {
            throw new Exception('carpool.internal start transaction fail');
        }
        $now =time(NULL);
        $arr_response = $db_proxy->selectForUpdate('pickride_info', array('pid', 'user_id', 'mileage', 'phone', 'driver_dev_id', 'passenger_dev_id'),array('and'=>           
            array(array('pid' => array('=' => $pid)),
            array('driver_id' =>  array('=' => $user_id)),  
            array('status' =>  array('=' => self::CARPOOL_STATUS_ACCEPTED)),                                        
        )));        
        if (false === $arr_response || !is_array($arr_response) )
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (count($arr_response) == 0)
        {
            $db_proxy->rollback();
            throw new Exception('carpool.not_found no pid found');
        }
        $mileage = intval($arr_response[0]['mileage']);
        $passenger_id = intval($arr_response[0]['user_id']);
        $passenger_dev_id = intval($arr_response[0]['passenger_dev_id']);
        
        $ret = $db_proxy->update('pickride_info', array('and'=>
            array(array('pid' => array('=' => $pid)),
            array('driver_id' =>  array('=' => $user_id)),  
            array('status' =>  array('=' => self::CARPOOL_STATUS_ACCEPTED)),              
        )), 'driver_id='.$user_id.',mtime ='.time(NULL).',status
        ='.self::CARPOOL_STATUS_DONE.',mileage ='. $mileage);
        if (false === $ret)
        {
            $db_proxy->rollback();
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) 
        {
            $db_proxy->rollback();
            throw new Exception('carpool.not_found this pid not exists');
        }       
        $db_proxy->commit();


        //更新 乘客 和 司机的 成单量， 可以不放到事务里

        $ret = $db_proxy->update('user_info', array('and'=>
            array(
                array('user_id' =>  
                    array('=' => $user_id)),                                                             
                )
            ), 'dcount = dcount + 1'); 

        $ret = $db_proxy->update('user_info', array('and'=>
            array(
                array('user_id' =>  
                    array('=' => $passenger_id)),                                                             
                )
            ), 'pcount = pcount + 1'); 


        $price = CarpoolConfig::ORDER_PRICE_NORMAL * $mileage / 1000;
     
        $msg = json_encode(array(
            'msg_type' => CarpoolConfig::$arrPushType['finish_order'],
            'msg_content' => array(
                'pid' => $pid,        
                'phone' => $user_name,   
                'price' => $price,   
                'mileage' => $mileage,  
            ),
            'msg_ctime' => time(NULL),
            'msg_expire' => 60,
        ));
        
        $arr_msg = array(
            'trans_type' => 2,
            'trans_content' => $msg,
        );
        $arr_user = array(
            array(
                'user_id' => $passenger_id,
                'device_id' => $passenger_dev_id,
            ),
        );
        
        //通知 乘客订单结束
        PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user);
        
        CLog::trace("order finish succ [account: %s, user_id : %d, pid : %s]", 
                    $user_name, $user_id, $pid);

        return true;
    }   
    
    
    public function getList($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['type'] ;
        $start = $arr_opt['start'] ;
        $limit = $arr_opt['limit'] ;
        $status = $arr_opt['status'] ;
        if (is_null($start)) 
        {
            $start = 0;
        }
        if (is_null($limit))
        {
            $limit = 100;
        }
        if (is_null($status)) 
        {
            $status = 0;
        }
        if ($limit > CarpoolConfig::CARPOOL_PAGE_LIMIT) {
            throw new Exception('carpool.param limit wrong');
        }
        if ($start < 0) 
        {
            throw new Exception('carpool.param start wrong');
        }
        if ($status < 0 || $status > 1) 
        {
            throw new Exception('carpool.param status wrong');
        }

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        $ret = true;
        if ($user_type ==  UserService::USERTYPE_DRIVER) 
        {
            $ret = $db_proxy->select('pickride_info', '*',
                array(
                    'and'=>                   
                        array(
                            array('driver_id' =>  array('=' => $user_id)),  
                            array('or' =>  
                                array(
                                    array('status' =>array('<>' => self::CARPOOL_STATUS_CREATE)),
                                    array('ctime' =>array('>' => time(NULL) - CarpoolConfig::CARPOOL_ORDER_TIMEOUT)),
                                )        
                            )
                        )                                                  
                    
                ),
                array( 'start' => $start, 'limit' => $limit + 1, 'order_by' => array('ctime' => 'desc'))
            );          
        }
        else 
        {
            $ret = $db_proxy->select('pickride_info', '*',
                array(
                    'and'=>                   
                        array(
                            array('user_id' =>  array('=' => $user_id)),  
                            array('or' =>  
                                array(
                                    array('status' =>array('<>' => self::CARPOOL_STATUS_CREATE)),
                                    array('ctime' =>array('>' => time(NULL) - CarpoolConfig::CARPOOL_ORDER_TIMEOUT)),
                                )        
                            )
                        )
                ),
                array( 'start' => $start, 'limit' => $limit + 1, 'order_by' => array('ctime' => 'desc'))
            );          
        }  

        if (false === $ret)
        {
            throw new Exception('carpool.internal select DB failed');
        }
        $has_more = 0;
        if (count($ret) > $limit) 
        {
            $has_more = 1;
            $ret = array_slice($ret,0, $limit);
        }
        foreach($ret as $key => &$value)
        {
            unset($value['user_id']);    
            unset($value['driver_id']);    
            unset($value['id']);    
            unset($value['passenger_id']);              
            unset($value['seat']);                    
            $value['src_gps'] = $value['src_latitude']. ','.$value['src_longitude'];
            $value['dest_gps'] = $value['dest_latitude']. ','.$value['dest_longitude'];
            if ($user_type ==  UserService::USERTYPE_PASSENGER) 
            {
                $value['phone'] = $value['driver_phone'];                
            }
            unset($value['driver_phone']);
            unset($value['src_latitude']);  
            unset($value['src_longitude']);  
            unset($value['dest_latitude']);  
            unset($value['dest_longitude']);  
            unset($value['driver_dev_id']);  
            unset($value['passenger_dev_id']); 
            $value['mileage'] = intval($ret['mileage']);
            $value['price'] = CarpoolConfig::ORDER_PRICE_NORMAL * intval($value['mileage'])/ 1000;

        }
        CLog::trace("order list succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return array(
            'list'     => $ret,
            'has_more' => $has_more,
        );
    }   

    public function nearby($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id']; 

        $gps_arr = explode(',', $arr_req['gps']);        

        if(!is_array($gps_arr) || count($gps_arr) !=2 ) {
            throw new Exception('carpool.param invalid gps');
        }

        $distance_instance = DistanceCompute::getInstance();
        $arr_range = $distance_instance->get_bound($gps_arr[0], $gps_arr[1],NotifyConfig::$NotifyRectangleRange);  
        $condition = array(
            'and' => array(
                array(
                        'src_latitude' => array(
                                '>' => $arr_range['min_latitude'],
                        ),
                ),
                array(
                        'src_latitude' => array(
                                '<' => $arr_range['max_latitude'],
                        ),
                ),
                array(
                        'src_longitude' => array(
                                '>' => $arr_range['min_longitude'],
                        ),
                ),
                array(
                        'src_longitude' => array(
                                '<' => $arr_range['max_longitude'],
                        ),
                ),
                array(
                        'status' => array(
                                '=' => self::CARPOOL_STATUS_CREATE,
                        ),
                ),
            ),
        );
        $append_condition = array(
            'start' => 0, 
            'limit' => 100,
            'order_by' => array('ctime' => 'desc')
        );
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        
        $ret = $db_proxy->select('pickride_info', '*', $condition, $append_condition);
        if (false === $ret || count($ret) == 0)
        {
            return array(
                'list' => array(),
            );
        }
        //直接按照矩形返回， 后面订单多可再做一次半径筛选
        $arr_user_list =  array();
        foreach($ret as $key => &$value)
        {
            if($value['ctime'] + CarpoolConfig::CARPOOL_ORDER_TIMEOUT < time(NULL)){
                unset($ret[$key]);
                continue;
            }
            $arr_user_list[] = intval($value['user_id']);
            unset($value['driver_id']);    
            unset($value['driver_phone']);    
            unset($value['id']);                  
            unset($value['driver_dev_id']);  
            unset($value['passenger_dev_id']); 
            $value['src_gps'] = $value['src_latitude']. ','.$value['src_longitude'];
            $value['dest_gps'] = $value['dest_latitude']. ','.$value['dest_longitude'];
            
            unset($value['driver_phone']);
            unset($value['src_latitude']);  
            unset($value['src_longitude']);  
            unset($value['dest_latitude']);  
            unset($value['dest_longitude']);  
            $value['mileage'] = intval($value['mileage']);
            $value['price'] = CarpoolConfig::ORDER_PRICE_NORMAL * intval($value['mileage'])/ 1000;
            $value['timeout'] = CarpoolConfig::CARPOOL_ORDER_TIMEOUT;
        }
        // added by zl 判断$arr_user_list是否为空
        if (0 == count($arr_user_list))
        {
            CLog::trace("user_list is null");
            return array(
                'list' => array(),
            );
        }
        // added by zl
        
        $arr_response = $db_proxy->select('user_info', array('user_id', 'name', 'sex', 'status'),array('and'=>array(
            array('user_id' =>  array('in' => $arr_user_list)),
                ))
        );
        if (false === $arr_response || !is_array($arr_response) || 0 == count($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed [sql: ' . 
                $db_proxy->getLastSQL() . ']');
        }
        $user_map = array();
        foreach($arr_response as $k => $v)
        {
            $user_map[intval($v['user_id'])] = $v;
        }
        foreach($ret as $key => &$value)
        {
            if(!isset($user_map[intval($value['user_id'])]))
            {
                continue;
            }
            $info = $user_map[intval($value['user_id'])];
            $value['name'] = $info['name'];
            $value['sex'] = intval($info['sex']);
            $value['status'] = intval($info['status']);
            $uk = UserService::api_encode_uid(intval($value['user_id'])); 
            $now = time(NULL);
            $value['head'] = CarpoolConfig::$domain."/rest/2.0/carpool/image?method=thumbnail&ctype=1&devuid=1&uk=$uk&timestamp=$now&sign=".hash_hmac('sha1', "$uk:$now", CarpoolConfig::$s3SK, false);
            unset($value['user_id']);
        }
        CLog::trace("nearby list succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return array(
            'list'     => $ret,
        );
    }   


    public function query($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['type'] ;
        $pid = $arr_req['pid'] ;

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        $arr_response = $db_proxy->select('pickride_info', '*',array('and'=>           
            array(array('pid' =>  array('=' => $pid)),                          
        )));
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) 
        {
            throw new Exception('carpool.not_found pid not exist');
        }
        $ret = $arr_response[0];
        unset($ret['id']);
        unset($ret['pid']);
        $ret['src_gps'] = $ret['src_latitude']. ','.$ret['src_longitude'];
        $ret['dest_gps'] = $ret['dest_latitude']. ','.$ret['dest_longitude'];
        unset($ret['src_latitude']);  
        unset($ret['src_longitude']);  
        unset($ret['dest_latitude']);  
        unset($ret['dest_longitude']);   
        if($user_id == $ret['user_id'])
        {
            //乘客端
            $ret['phone'] = $ret['driver_phone'];
            $uk = UserService::api_encode_uid(intval($ret['driver_id'])); 
            $to_uid = intval($ret['driver_id']);
  
        }
        else
        {
            //司机端
            $uk = UserService::api_encode_uid(intval($ret['user_id'])); 
            $to_uid = intval($ret['user_id']);
            
        }
         
        $now = time(NULL);  
        $ret['seat'] = intval($ret['seat']);
        $ret['ctime'] = intval($ret['ctime']);
        $ret['mtime'] = intval($ret['mtime']);
        if ($ret['status'] == self::CARPOOL_STATUS_CREATE && $ret['ctime'] < time(NULL) - CarpoolConfig::CARPOOL_ORDER_TIMEOUT) 
        {
            $ret['status'] = self::CARPOOL_STATUS_TIMEOUT;
        }
        $ret['status'] = intval($ret['status']);
        $ret['phone'] = intval($ret['phone']);
        $ret['mileage'] = intval($ret['mileage']);
        $ret['price'] = CarpoolConfig::ORDER_PRICE_NORMAL * $mileage / 1000;
        $ret['name'] = '';
        unset($ret['driver_phone']);
        unset($ret['driver_id']);        
        unset($ret['user_id']);
        unset($ret['driver_dev_id']);
        unset($ret['passenger_dev_id']);
        $ret['timeout'] = CarpoolConfig::CARPOOL_ORDER_TIMEOUT;

        if($to_uid ===0)
        {
            CLog::trace("order query succ [account: %s, user_id : %d ]", $user_name, $user_id);
            return $ret;
        }
        
        $arr_response = $db_proxy->select('user_info', array('name', 'phone'), array('and' => array(array('user_id' => array('=' => $to_uid,),), ),));
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) 
        {
            throw new Exception('carpool.not_found uid not exist');
        }
        $ret['name'] = $arr_response[0]['name'];
        CLog::trace("order query succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return $ret;
    }   
    public function batch_query($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id'] ;
        $list = $arr_req['list'] ;
        $arr_list = json_decode($list);

        if(!$arr_list || count($arr_list) > CarpoolConfig::CARPOOL_PAGE_LIMIT)
        {
            throw new Exception("carpool.param list illegal");
        }

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        //只支持司机
        
        $arr_response = $db_proxy->select('pickride_info', '*',array('and'=>           
            array(
                array('pid' =>  array('in' => $arr_list)),
                //array('driver_id' =>  array('=' => $user_id)),
            ),                          
        ), array( 'start' => 0, 'limit' => CarpoolConfig::CARPOOL_PAGE_LIMIT));
        
        
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        foreach($arr_response as $key => &$value){
            unset($value['id']);
            $value['src_gps'] = $value['src_latitude']. ','.$value['src_longitude'];
            $value['dest_gps'] = $value['dest_latitude']. ','.$value['dest_longitude'];
            unset($value['src_latitude']);  
            unset($value['src_longitude']);  
            unset($value['dest_latitude']);  
            unset($value['dest_longitude']);  
            if($user_id == $value['user_id']){
                $value['phone'] = $value['driver_phone'];            
            }
            unset($value['driver_phone']);
            $value['seat'] = intval($value['seat']);
            $value['ctime'] = intval($value['ctime']);
            $value['mtime'] = intval($value['mtime']);
            if ($value['status'] == self::CARPOOL_STATUS_CREATE && $value['ctime'] < time(NULL) - CarpoolConfig::CARPOOL_ORDER_TIMEOUT) {
                $value['status'] = self::CARPOOL_STATUS_TIMEOUT;
            }
            $value['status'] = intval($value['status']);
            $value['phone'] = intval($value['phone']);            
            unset($value['driver_id']);            
            unset($value['user_id']);
            $value['timeout'] = CarpoolConfig::CARPOOL_ORDER_TIMEOUT;
            unset($value['driver_dev_id']);
            unset($value['passenger_dev_id']);
            $value['mileage'] = intval($ret['mileage']);
            $value['price'] = CarpoolConfig::ORDER_PRICE_NORMAL * intval($value['mileage'])/ 1000;

        }
        
        CLog::trace("order batch query succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return array('list' => $arr_response);
    }   
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
