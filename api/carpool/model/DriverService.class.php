<?php

class DriverService
{
    

    private static $instance = NULL;
    
    const TABLE_USER_INFO = 'user_info';
    const TABLE_DRIVER_INFO = 'driver_info';

    /**
     * @return StreamService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new DriverService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {

    }

    public function report($arr_req, $arr_opt)
    {

        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        $gps = $arr_req['gps'] ;
        $devuid = $arr_req['devuid'];
        
        Utils::check_string($gps, 1, 256); 
        
        $gps_arr = explode(',', $gps);        
        if(!is_array($gps_arr) || count($gps_arr) !=2 ) {
            throw new Exception('carpool.param invalid gps');
        }
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy) {
            throw new Exception('carpool.internal connect to the DB failed');
        }
        $condition = array(
            'and' => array(
                array(
                    'user_id' => array(
                        '=' => $user_id,
                    ),
                ),          

            ),
        );
        
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('phone', 'driver_status'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.param user_id not exist');
        }
        //司机没认证，不更新他的表
        if($arr_response[0]['driver_status'] == UserService::USERSTATUS_INIT)
        {
            CLog::trace("the driver is not authenticated [user_id: %s, gps: %s]",
                        $user_id, $gps);
            return true;
        }

        $now = time(NULL);
        $row = array(
            'user_id'   => $user_id,
            'dev_id'    => $devuid,
            'dev_id_sign' => crc32($devuid),     
            'longitude' => $gps_arr[1],
            'latitude'  => $gps_arr[0],                                             
            'ctime'         => $now,
            'mtime'         => $now,
        ); 
        $duplicate_key = array(
            'dev_id'    => $devuid,
            'dev_id_sign' => crc32($devuid),
            'longitude' => $gps_arr[1],
            'latitude'  => $gps_arr[0],         
            'mtime'     => $now,
        );  
        //$db_proxy->startTransaction();
        $ret = $db_proxy->insert(self::TABLE_DRIVER_INFO, $row, $duplicate_key);
        if (false === $ret) {
            throw new Exception('carpool.internal insert to the DB failed [err_code: ' . 
                                $db_proxy->getErrorCode() . ' err_msg: ' . $db_proxy->getErrorMsg());
        }
        
        CLog::trace("driver repot succ [account: %s, user_id: %d, gps: %s]", 
                    $user_name, $user_id, $gps);
                    
        return true;

    }
    
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
