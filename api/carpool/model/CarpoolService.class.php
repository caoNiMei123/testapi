<?php

class CarpoolService
{
    const CARPOOL_STATUS_CREATE  = 0;
    const CARPOOL_STATUS_DOING  = 1;
    const CARPOOL_STATUS_ACCEPTED  = 2;
    const CARPOOL_STATUS_CANCLED  = 3;    
    const CARPOOL_STATUS_DONE = 4;
    const CARPOOL_STATUS_TIMEOUT  = 5;

    const USERTYPE_DRIVER =1;
    const USERTYPE_PASSENGER=2;

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
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $type = $arr_req['user_type'] ;
        $src = $arr_req['src'] ;
        $dest = $arr_req['dest'] ;
        $src_gps = $arr_req['src_gps'] ;
        $dest_gps = $arr_req['dest_gps'] ;        
        $ret = Utils::check_int($type, 1, 2); 
        if (false == $ret) {
            throw new Exception('carpool.param invalid type');
        }
        $ret = Utils::check_string($src, 1, 256); 
        if (false == $ret) {
            throw new Exception('carpool.param invalid src');
        }
        $ret = Utils::check_string($dest, 1, 256); 
        if (false == $ret) {
            throw new Exception('carpool.param invalid dest');
        }
        $ret = Utils::check_string($src_gps, 1, 256); 
        if (false == $ret) {
            throw new Exception('carpool.param invalid src_gps');
        }
        $ret = Utils::check_string($dest_gps, 1, 256); 
        if (false == $ret) {
            throw new Exception('carpool.param invalid dest_gps');
        }
        $src_gps_arr = explode(',', $src_gps);
        $dest_gps_arr = explode(',', $dest_gps);
        if(!is_array($src_gps_arr) || !is_array($dest_gps_arr) || count($src_gps_arr) !=2 || count($dest_gps_arr) !=2) {
            throw new Exception('carpool.param invalid gps');
        }

        
        $row = array();
        $now = time(NULL);
        $pid = Utils::sign64(time(NULL).$user_id.$src_gps.$dest_gps.rand());   
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }

        $arr_response = $db_proxy->select('pickride_info', array('pid'),array('and'=>
            array(array('user_id' =>  array('=' => $user_id)),  
            array('ctime' =>  array('>' => ($now - CarpoolConfig::CARPOOL_ORDER_TIMEOUT))), 
            array('status' =>  array('>' => -1)), 
            array('status' =>  array('<' => self::CARPOOL_STATUS_CANCLED)),               
        )));        
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 != count($arr_response)) {
            throw new Exception('carpool.duplicate has another carpool doing');
        }
        



        $row = array(
            'pid' => $pid,
            'user_id'       => $user_id,
            'phone'       => intval($user_name),
            'src'           => $src,
            'dest'          => $dest,
            'src_longitude' => $src_gps_arr[1],
            'src_latitude'  => $src_gps_arr[0], 
            'dest_longitude'=> $dest_gps_arr[1],                
            'dest_latitude' => $dest_gps_arr[0],                                        
            'ctime'         => $now,
            'mtime'         => $now,
        ); 
        
        //$db_proxy->startTransaction();
        $ret = $db_proxy->insert('pickride_info', $row);
        if (false === $ret)
        {
            if($db_proxy->getErrorCode() == 1062)
                throw new Exception('carpool.duplicate pid alrealdy created');
            else    
            {
                throw new Exception('carpool.internal insert to the DB failed [err_code: ]' .
                                    $db_proxy->getErrorCode() . ' err_msg: ' . $db_proxy->getErrorMsg());
            }
        }       
        $arr_response = array(
            'pid' => $pid,  
            'timeout' =>CarpoolConfig::CARPOOL_ORDER_TIMEOUT,        
        );
        
        //抛起一个异步任务, 写task表
        $row = array(
            'pid' => $pid,
            'user_id'       => $user_id,
            'phone'           => $user_name,                                       
            'ctime'         => $now,
            'mtime'         => $now,
        ); 
        
        //$db_proxy->startTransaction();
        $ret = $db_proxy->insert('task_info_'.$pid%16, $row);
        if (false === $ret)
        {            
            throw new Exception('carpool.internal insert to the DB failed');
        }       
        CLog::trace("order create succ [account: %s, user_id : %d, pid : %s]", $user_name, $user_id, $pid);
        return $arr_response;
    }   
    public function cancel($arr_req, $arr_opt)
    {
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $user_type = intval($arr_req['user_type']) ;    
        $devuid = $arr_req['devuid'];
        $pid = $arr_req['pid'] ;
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }       
        $arr_response = $db_proxy->select('pickride_info', array('status', 'pid', 'user_id', 'driver_id', 'phone', 'driver_phone'),array('and'=>           
            array(array('pid' =>  array('=' => $pid)),                          
        )));
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.not_found pid not exist');
        }
        $status = $arr_response[0]['status'];
        $ret = false;
        $to_uid = 0;
        $to_phone = 0; 
        if ($user_type == self::USERTYPE_PASSENGER) {
            //乘客逻辑 
            if ($status != self::CARPOOL_STATUS_CREATE && $status != self::CARPOOL_STATUS_DOING) {
                throw new Exception('carpool.order_status status not allowed');
            }   
            $ret = $db_proxy->update('pickride_info', array('and'=>
                array(array('pid' => array('=' => $pid)),
                    array('user_id' =>  array('=' => $user_id)),                                  
            )), 'mtime ='.time(NULL).',status='.self::CARPOOL_STATUS_CANCLED); 
            $to_uid = intval($arr_response[0]['driver_id']);  
            $to_phone = $arr_response[0]['phone'];  
                
        }
        else {
            //司机逻辑             
            if ($status != self::CARPOOL_STATUS_DOING) {
                throw new Exception('carpool.order_status status not allowed');
            }  
            $ret = $db_proxy->update('pickride_info', array('and'=>
                array(array('pid' => array('=' => $pid)),
                    array('driver_id' =>  array('=' => $user_id)),                                   
            )), 'mtime ='.time(NULL).',status='.self::CARPOOL_STATUS_CANCLED);  
            $to_uid = intval($arr_response[0]['user_id']);
            $to_phone = $arr_response[0]['driver_phone'];  
        }
           
        if (false === $ret) {
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) {
            throw new Exception('carpool.not_found this pid not exists');
        }
        
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
	        	'trans_type' => 1,
	        	'trans_content' => $msg,
	        );
	        $arr_user = array(
	        	array(
		        	'user_id' => $to_uid,
		        	'device_id' => $devuid,
	        	),
	        );
	        
	        PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user, $user_type);
        }       

        //修改任务表
        $ret = $db_proxy->update('task_info_'.$pid%16, array('and'=>
            array(array('pid' => array('=' => $pid)),
                //array('status' =>  array('=' => )),              
        )), 'status=3, mtime ='.time(NULL));
        if (false === $ret)
        {            
            throw new Exception('carpool.internal update task info failed');
        }       
        CLog::trace("order cancel succ [account: %s, user_id : %d, pid : %s]", $user_name, $user_id, $pid);

        return true;
    }   



    public function accept($arr_req, $arr_opt)
    {               
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;        
        $pid = $arr_req['pid'] ;
		$devuid = $arr_req['devuid'];
		$user_type = $arr_req['user_type'];
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }
        $now =time(NULL);
        $arr_response = $db_proxy->select('pickride_info', array('pid', 'user_id', 'phone'),array('and'=>           
            array(array('ctime' =>  array('>' => $now - CarpoolConfig::CARPOOL_ORDER_TIMEOUT)), 
            array('status' =>  array('=' => self::CARPOOL_STATUS_CREATE)),                          
        )));        
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.not_found pid not exist');
        }
        $passenger_id = intval($arr_response[0]['user_id']);
        $passenger_phone = intval($arr_response[0]['phone']);
        $arr_response = $db_proxy->select('driver_info', array('latitude', 'longitude'),array('and'=>           
            array(array('user_id' =>  array('=' => $user_id)), 
            array('status' =>  array('=' => 0)),                          
        )));        
        if (false === $arr_response || !is_array($arr_response) )
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (count($arr_response) == 0)
        {
            throw new Exception('carpool.invalid_driver no gps found');
        }

        $arr_response = $db_proxy->select('pickride_info', array('pid'),array('and'=>           
            array(array('driver_id' =>  array('=' => $user_id)), 
            array('status' =>  array('=' => self::CARPOOL_STATUS_DOING)),                          
        )));        
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 != count($arr_response)) {
            throw new Exception('carpool.duplicate another pid doing');
        }


        $latitude = $arr_response[0]['latitude'];
        $longitude = $arr_response[0]['longitude'];
        $ret = $db_proxy->update('pickride_info', array('and'=>
            array(array('pid' => array('=' => $pid)),
                array('user_id' =>  array('!=' => $user_id)),  
                array('status' =>  array('=' => self::CARPOOL_STATUS_CREATE)),              
        )), 'driver_id='.$user_id.',driver_phone = '.intval($user_name).',mtime ='.time(NULL).',status ='.self::CARPOOL_STATUS_DOING);
        if (false === $ret) {
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) {
            throw new Exception('carpool.param this pid not exists');
        }

        $msg = json_encode(array(
            'msg_type' => CarpoolConfig::$arrPushType['accept_order'],
            'msg_content' => array(
                'pid' => $pid,
                'phone' => $user_name,
                'user_id'=> $user_id,
                'latitude'=>$latitude,
                'longitude'=>$longitude,
            ),
            'msg_ctime' => time(NULL),
            'msg_expire' => 60,
        ));
        
        $arr_msg = array(
        	'trans_type' => 1,
        	'trans_content' => $msg,
        );
        $arr_user = array(
        	array(
	        	'user_id' => $passenger_id,
	        	'device_id' => $devuid,
        	),
        );
        
        //通知 乘客我已经接单
        PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user, $user_type);
        
        CLog::trace("order accept succ [account: %s, user_id : %d, pid : %d, passenger: %d ]", 
        			$user_name, $user_id, $pid, $passenger_phone);

        return true;
    }   

    public function finish($arr_req, $arr_opt)
    {               
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;        
        $pid = $arr_req['pid'] ;
        $devuid = $arr_req['devuid'];
        $user_type = $arr_req['user_type'];            
        
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }   
        $now =time(NULL);
        $arr_response = $db_proxy->select('pickride_info', array('pid', 'user_id', 'phone'),array('and'=>           
            array(array('pid' => array('=' => $pid)),
            array('driver_id' =>  array('=' => $user_id)),  
            array('status' =>  array('=' => self::CARPOOL_STATUS_DOING)),                                        
        )));        
        if (false === $arr_response || !is_array($arr_response) )
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (count($arr_response) == 0)
        {
            throw new Exception('carpool.not_found no pid found');
        }
        $passenger_id = intval($arr_response[0]['user_id']);
        $ret = $db_proxy->update('pickride_info', array('and'=>
            array(array('pid' => array('=' => $pid)),
            array('driver_id' =>  array('=' => $user_id)),  
            array('status' =>  array('=' => self::CARPOOL_STATUS_DOING)),              
        )), 'driver_id='.$user_id.',mtime ='.time(NULL).',status ='.self::CARPOOL_STATUS_DONE);
        if (false === $ret) {
            throw new Exception('carpool.internal insert to the DB failed');
        }
        if ($ret != 1) {
            throw new Exception('carpool.not_found this pid not exists');
        }       

        $msg = json_encode(array(
            'msg_type' => CarpoolConfig::$arrPushType['finish_order'],
            'msg_content' => array(
                'pid' => $pid,        
                'phone' => $user_name,        
            ),
            'msg_ctime' => time(NULL),
            'msg_expire' => 60,
        ));
        
        $arr_msg = array(
        	'trans_type' => 1,
        	'trans_content' => $msg,
        );
        $arr_user = array(
        	array(
	        	'user_id' => $passenger_id,
	        	'device_id' => $devuid,
        	),
        );
        
        //通知 乘客订单结束
        PushPorxy::getInstance()->push_to_single(4, $arr_msg, $arr_user, $user_type);
        
        CLog::trace("order finish succ [account: %s, user_id : %d, pid : %s]", 
        			$user_name, $user_id, $pid);

        return true;
    }   



    
    
    public function getList($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        $start = $arr_opt['start'] ;
        $limit = $arr_opt['limit'] ;
        $status = $arr_opt['status'] ;
        if (is_null($start)) {
            $start = 0;
        }
        if (is_null($limit)) {
            $limit = 100;
        }
        if (is_null($status)) {
            $status = 0;
        }
        if ($limit > CarpoolConfig::CARPOOL_PAGE_LIMIT) {
            throw new Exception('carpool.param limit wrong');
        }
        if ($start < 0) {
            throw new Exception('carpool.param start wrong');
        }
        if ($status < 0 || $status > 1) {
            throw new Exception('carpool.param status wrong');
        }

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }       
        $ret = true;
        if ($user_type ==  self::USERTYPE_DRIVER) {
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
        else {
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
        if (count($ret) > $limit) {
            $has_more = 1;
            $ret = array_slice($ret,0, $limit);
        }
        foreach($ret as $key => &$value){
            unset($value['user_id']);    
            unset($value['driver_id']);    
            unset($value['id']);    
            unset($value['passenger_id']);              
            unset($value['seat']);                    
            $value['src_gps'] = $value['src_latitude']. ','.$value['src_longitude'];
            $value['dest_gps'] = $value['dest_latitude']. ','.$value['dest_longitude'];
            if ($user_type ==  self::USERTYPE_PASSENGER) {
                $value['phone'] = $value['driver_phone'];                
            }
            unset($value['driver_phone']);
            unset($value['src_latitude']);  
            unset($value['src_longitude']);  
            unset($value['dest_latitude']);  
            unset($value['dest_longitude']);   

        }
        CLog::trace("order list succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return array(
            'list'     => $ret,
            'has_more' => $has_more,
        );
    }   
    public function query($arr_req, $arr_opt)
    {

        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        $pid = $arr_req['pid'] ;

        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }       
        $arr_response = $db_proxy->select('pickride_info', '*',array('and'=>           
            array(array('pid' =>  array('=' => $pid)),                          
        )));
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
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
        if($user_id == $ret['user_id']){
            $ret['phone'] = $ret['driver_phone'];            
        }
        else if ($user_id == $ret['driver_id']){
            $ret['phone'] = $ret['user_phone'];            
        }        
        else {
            throw new Exception('carpool.invalid_user user_id illegal');
        }
        $ret['seat'] = intval($ret['seat']);
        $ret['ctime'] = intval($ret['ctime']);
        $ret['mtime'] = intval($ret['mtime']);
        if ($ret['status'] == self::CARPOOL_STATUS_CREATE && $ret['ctime'] < time(NULL) - CarpoolConfig::CARPOOL_ORDER_TIMEOUT) {
            $ret['status'] = self::CARPOOL_STATUS_TIMEOUT;
        }
        $ret['status'] = intval($ret['status']);
        $ret['phone'] = intval($ret['phone']);
        unset($ret['driver_phone']);
        unset($ret['driver_id']);
        unset($ret['user_phone']);
        unset($ret['user_id']);
        $ret['timeout'] = CarpoolConfig::CARPOOL_ORDER_TIMEOUT;
        CLog::trace("order query succ [account: %s, user_id : %d ]", $user_name, $user_id);
        return $ret;
    }   
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
