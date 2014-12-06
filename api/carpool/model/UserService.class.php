<?php

class UserService
{
    private static $instance = NULL;
    
    const TABLE_USER_INFO = 'user_info';
    const TABLE_SECSTR_INFO = 'secstr_info';
    const USERTYPE_DRIVER =1;
    const USERTYPE_PASSENGER=2;
    const USERSTATUS_INACTIVE = 0;
    const USERSTATUS_NORMAL = 1;
    const USERSTATUS_ACTIVE = 2;

    
    /**
     * @return PushService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new UserService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {
            
    }

    public function register($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        // 检查account
        $account = $arr_req['account'];
        $type = $arr_req['type'];
        $ret = Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);
        if (false == $ret) {
            throw new Exception('carpool.param invalid account length [max_len: ' . 
                                CarpoolConfig::USER_MAX_ACCOUNT_LENGTH . ']');
        }

        $ret = Utils::is_valid_phone($account);
        if (false == $ret) {
            throw new Exception('carpool.param invalid account [account: ' . $account . ']');
        }
        if (is_null($type)||($type != self::USERTYPE_DRIVER && $type !=self::USERTYPE_PASSENGER)) {
            throw new Exception('carpool.param invalid type');
        }

        
        
        $secstr = $arr_req['secstr'];
        if (!self::checkStr($account, $secstr)) {
            throw new Exception('carpool.secstr secstr error');
        }
        $now = time();
        $row = array();

        if ($type == self::USERTYPE_DRIVER) {
            if (is_null($arr_opt['detail'])) {
                throw new Exception('carpool.param detail is null');
            }
            $arr_detail = json_decode($arr_opt['detail'], true);
            if (!is_array($arr_detail)) {
                throw new Exception('carpool.param detail is not array');
            }
            if (!isset($arr_detail['car_num']) || !isset($arr_detail['car_engine_num']) || !isset($arr_detail['car_type'])) {
                throw new Exception('carpool.param detail param is wrong');
            }
            
            $ret = Utils::check_string($arr_detail['car_num'], 1, CarpoolConfig::USER_MAX_CAR_NUM_LENGTH);
            if (false == $ret) {
                throw new Exception('carpool.param invalid car_num');
            }
            $ret = Utils::check_string($arr_detail['car_engine_num'], 1, CarpoolConfig::USER_MAX_CAR_ENGINE_NUM_LENGTH);
            if (false == $ret) {
                throw new Exception('carpool.param invalid car_engine_num');
            }
            $ret = Utils::check_string($arr_detail['car_type'], 1, CarpoolConfig::USER_MAX_CAR_TYPE_LENGTH);
            if (false == $ret) {
                throw new Exception('carpool.param invalid car_type');
            }
            
        
            $row = array(               
                'phone'     => $account,
                'car_type'  => $arr_detail['car_type'],
                'car_num'  => $arr_detail['car_num'],
                'car_engine_num'  =>$arr_detail['car_engine_num'],
                'user_type' => self::USERTYPE_DRIVER,
                'ctime'     => $now,
                'mtime'     => $now,
            );
        }
        else {
            $row = array(               
                'phone'     => $account,
                'user_type' => self::USERTYPE_PASSENGER,
                'ctime'     => $now,
                'mtime'     => $now,
            );
        }       
        
        
        // 3. 访问数据库
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   
        
        $ret = $dbProxy->insert(self::TABLE_USER_INFO, $row);
        if (false === $ret)
        {
            $error_code = $dbProxy->getErrorCode();
            $error_msg = $dbProxy->getErrorMsg();

            if ( $error_code == 1062) {
                throw new Exception('carpool.duplicate account already exists');
            }
            
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                                $error_code . ', error_msg: ' . $error_msg . ']');
        }

        $condition = array(
            'and' => array(
                array(
                    'phone' => array(
                        '=' => $account,
                    ),
                ),
            ),
        );
        $arr_response = $dbProxy->select(self::TABLE_USER_INFO, array('user_id','user_type'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.internal register fail');
        }
        $user_id = intval($arr_response[0]['user_id']);
        $user_type = intval($arr_response[0]['user_type']);
        $uinfo = self::_encrypt_uinfo($account, $user_id, $user_type);
        setcookie('CPUINFO', $uinfo, time() + CarpoolConfig::USER_COOKIE_EXPIRE_TIME);
        CLog::trace("register succ [account: %s, type : %d, user_id : %d]", $account, $type,$user_id);
    }
    
    public function sendsms($arr_req, $arr_opt)
    {
        $account = $arr_req['account'];
        $ret = Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account length [max_len: ' . 
                                CarpoolConfig::USER_MAX_ACCOUNT_LENGTH . ']');
        }
        
        $ret = Utils::is_valid_phone($account);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account [account: ' . $account . ']');
        }
                
        $sec_str = Utils::generate_rand_str(6, '1234567890');
        $row = array(               
            'phone'     => $account,
            'secstr'    => $sec_str,                
            'ctime'     => time(NULL),
        );

        

        // 3. 访问数据库
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   

        $condition = array(
            'and' => array(
                array(
                    'phone' => array(
                        '=' => $account,
                    ),
                ),
                array(
                    'ctime' => array(
                        '<' => time(NULL) + CarpoolConfig::CARPOOL_SECSTR_TIMEOUT,
                    ),
                ),
            ),
        );        
        $arr_response = $dbProxy->select(self::TABLE_SECSTR_INFO, array('id'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 != count($arr_response)) {
            throw new Exception('carpool.duplicate already has a sectr');
        }

        
        $ret = $dbProxy->insert(self::TABLE_SECSTR_INFO, $row);
        if (false === $ret)
        {
            $error_code = $dbProxy->getErrorCode();
            $error_msg = $dbProxy->getErrorMsg();

            if ( $error_code == 1062) {
                throw new Exception('carpool.duplicate account already exists');
            }
            
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                                $error_code . ', error_msg: ' . $error_msg . ']');
        }
        //发短信
        if (CarpoolConfig::$debug) {
            return true;  
        }
        SmsPorxy::getInstance()->push_to_single($account, $sec_str);
        CLog::trace("sendsms succ [account: %s, secstr : %s]", $account, $sec_str);            
    }


    public function checkStr($account, $secstr)
    {
        if (CarpoolConfig::$debug) {
            return true;  
        }

        // 3. 访问数据库
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            return false;
        }   

        $condition = array(
            'and' => array(
                array(
                    'phone' => array(
                        '=' => $account,
                    ),
                ),
                array(
                    'ctime' => array(
                        '<' => time(NULL) + CarpoolConfig::CARPOOL_SECSTR_TIMEOUT,
                    ),
                ),
            ),
        );        
        $arr_response = $dbProxy->select(self::TABLE_SECSTR_INFO, array('id'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (1 != count($arr_response)) {
            return false;
        }
        if($arr_response[0]['secstr'] != $secstr) {
            return false;
        }
        
        return true;

        
    }

    
    
    public function login($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        $account = $arr_req['account'];
        $ret = Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account length [max_len: ' . 
                                CarpoolConfig::USER_MAX_ACCOUNT_LENGTH . ']');
        }
        $type = $arr_req['type'];   
        if (is_null($type)||($type != self::USERTYPE_DRIVER && $type !=self::USERTYPE_PASSENGER)) {
            throw new Exception('carpool.param invalid type');
        } 
        $ret = Utils::is_valid_phone($account);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account [account: ' . $account . ']');
        }

        
        $secstr = $arr_req['secstr'];
        if (!self::checkStr($account, $secstr)) {
            throw new Exception('carpool.secstr secstr error');
        }
        
        // 3. 访问数据库
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   

        $condition = array(
            'and' => array(
                array(
                    'phone' => array(
                        '=' => $account,
                    ),
                ),
            ),
        );
        
        $arr_response = $dbProxy->select(self::TABLE_USER_INFO, array('user_id','user_type'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            if ($type == self::USERTYPE_DRIVER){
                throw new Exception('carpool.invalid_user login fail');
            }
            $now = time(NULL);
            $row = array(               
                'phone'     => $account,
                'user_type' => self::USERTYPE_PASSENGER,
                'ctime'     => $now,
                'mtime'     => $now,
            );
            $ret = $dbProxy->insert(self::TABLE_USER_INFO, $row);
            if (false === $ret)
            {
                $error_code = $dbProxy->getErrorCode();
                $error_msg = $dbProxy->getErrorMsg();
                throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                    $error_code . ', error_msg: ' . $error_msg . ']');
            }
            
            $arr_response = $dbProxy->select(self::TABLE_USER_INFO, array('user_id','user_type'), $condition);
            if (false === $arr_response || !is_array($arr_response) || 0 == count($arr_response))
            {
                throw new Exception('carpool.internal select from the DB failed');
            }

        }
                
        // 4. 设置cookie
        $user_id = intval($arr_response[0]['user_id']);
        $uinfo = self::_encrypt_uinfo($account, $user_id, $type);          
        setcookie('CPUINFO', $uinfo, time() + CarpoolConfig::USER_COOKIE_EXPIRE_TIME);
        
        CLog::trace("login succ [account: %s, user_id : %d, type : %d]", $account, $user_id, $type);
    }   
       
    
    public function report($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        $client_id = $arr_req['client_id'];
        $devuid = $arr_req['devuid'];
        $ret = Utils::check_string($client_id, 1, 64);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid client_id length [max_len: 64]');
        }
        // 2. 访问数据库
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   

        $condition = array(
            'and' => array(
                array(
                    'user_id' => array(
                        '=' => $user_id,
                    ),
                ),
                array(
                    'status' => array(
                        '=' => 0,
                    ),
                ),

            ),
        );
        
        $arr_response = $dbProxy->select(self::TABLE_USER_INFO, array('phone'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.param user_id not exist');
        }
           
        $now = time(NULL);
        $row = array(               
            'user_id'     => $user_id,
            'client_id'     => $client_id,
            'dev_id'     => $devuid,
            'dev_id_sign'     => crc32($devuid),
            'ctime'     => $now,
            'mtime'     => $now,
        );
        $duplicate = array(
        	'client_id' => $client_id,
			'dev_id'     => $devuid,
            'dev_id_sign' => crc32($devuid),
            'mtime' => $now,
            'status'=>0,
        );
        $ret = $dbProxy->insert('device_info', $row, $duplicate);
        if (false === $ret)
        {
            $error_code = $dbProxy->getErrorCode();
            $error_msg = $dbProxy->getErrorMsg();
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                $error_code . ', error_msg: ' . $error_msg . ']');
        }
        
        CLog::trace("user report succ [account: %s, dev_id: %s, client_id : %s]", 
        			$user_name, $devuid, $client_id);
        			
        return true;
    }

    
    public static function _encrypt_uinfo($user_name, $user_id, $user_type)
    {
        $len =  rand(10,20);
        return base64_encode(Ucrypt::rc4(CarpoolConfig::$cookieSK, 
                                         Utils::getRandStr($len) . ':' .time(NULL) . ':' . 
                                         $user_name . ':' . Utils::getRandStr($len) . ':' . 
                                         $user_id.':'.$user_type));
    }
    
    public static function _decrypt_uinfo($sk_uinfo)
    {
        $rawData =  Ucrypt::rc4( CarpoolConfig::$cookieSK ,base64_decode($sk_uinfo));
        $rawArray = explode(':',$rawData);
        if (!is_array($rawArray) || count($rawArray) != 6)
        {
            return false;
        }
        if (time(NULL) - intval($rawArray[1]) > CarpoolConfig::USER_COOKIE_EXPIRE_TIME)
        {
            return false;
        }
        return array(
            'user_name' => intval($rawArray[2]),
            'user_id'   => intval($rawArray[4]),
            'user_type'   => intval($rawArray[5]),
        );
    }
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
