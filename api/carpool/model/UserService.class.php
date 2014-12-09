<?php

class UserService
{
    private static $instance = NULL;
    
    const TABLE_USER_INFO = 'user_info';
    const TABLE_SECSTR_INFO = 'secstr_info';
    const USERTYPE_DRIVER =1;
    const USERTYPE_PASSENGER=2;
    const USERSTATUS_INACTIVE = 0;
    const USERSTATUS_CHECK = 1;
    const USERSTATUS_ACTIVE = 2;
    const TOKENTYPE_PHONE=1;
    const TOKENTYPE_EMAIL=2;
    const REASONTYPE_REG=1;
    const REASONTYPE_PASSENGER_AUTH=2;

    
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
        if (!self::check_str($account, $secstr, CarpoolConfig::CARPOOL_SECSTR_PHONE_TIMEOUT)) {
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
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   
        
        $ret = $db_proxy->insert(self::TABLE_USER_INFO, $row);
        if (false === $ret)
        {
            $error_code = $db_proxy->getErrorCode();
            $error_msg = $db_proxy->getErrorMsg();

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
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('user_id','user_type'), $condition);
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
    
    public function get_token($arr_req, $arr_opt)
    {
        $account = $arr_req['account'];
        $ret = Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account length [max_len: ' . 
                CarpoolConfig::USER_MAX_ACCOUNT_LENGTH . ']');
        }

        $type = isset($arr_opt['type'])? $arr_opt['type']:self::TOKENTYPE_PHONE;
        $reason = isset($arr_opt['reason'])? $arr_opt['reason']:self::REASONTYPE_REG;

        if($type != self::TOKENTYPE_PHONE && $type != self::TOKENTYPE_EMAIL)
        {
            throw new Exception('carpool.param invalid type' );
        }
        if($reason != self::REASONTYPE_REG && $reason != self::REASONTYPE_PASSENGER_AUTH)
        {
            throw new Exception('carpool.param invalid reason' );
        }

        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   


        //目前reg 只支持手机， auth只支持邮箱
        switch($reason){
            case self::REASONTYPE_REG:
                $ret = Utils::is_valid_phone($account);
                if (false == $ret)
                {
                    throw new Exception('carpool.param invalid account [account: ' . $account . ']');
                }
                $sec_str = Utils::generate_rand_str(6, '1234567890');
                $row = array(               
                    'account'     => $account,
                    'secstr'    => $sec_str,                
                    'ctime'     => time(NULL),
                    'type'      => self::TOKENTYPE_PHONE, 
                );
                $timeout = CarpoolConfig::CARPOOL_SECSTR_PHONE_TIMEOUT;
                $condition = array(
                    'and' => array(
                        array(
                            'account' => array(
                                '=' => $account,
                            ),
                        ),
                        array(
                            'ctime' => array(
                                '<' => time(NULL) + $timeout,
                            ),
                        ),
                    ),
                );        
                $arr_response = $db_proxy->select(self::TABLE_SECSTR_INFO, array('id'), $condition);
                if (false === $arr_response || !is_array($arr_response))
                {
                    throw new Exception('carpool.internal select from the DB failed');
                }
                if (0 != count($arr_response)) {
                    throw new Exception('carpool.duplicate already has a sectr');
                }

                break;
            case self::REASONTYPE_PASSENGER_AUTH:
                $ret = Utils::is_valid_email($account);
                $user_id = $arr_opt['user_id'];
                if (false == $ret)
                {
                    throw new Exception('carpool.param invalid account [account: ' . $account . ']');
                }
                $sec_str = Utils::generate_rand_str(100, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
                $row = array(               
                    'account'     => $account,
                    'secstr'    => $sec_str,                
                    'ctime'     => time(NULL),
                    'type'      => self::TOKENTYPE_PHONE, 
                    'user_id'   => $user_id,
                );
                $timeout = CarpoolConfig::CARPOOL_SECSTR_EMAIL_TIMEOUT;
                break;
        }            
        
        $ret = $db_proxy->insert(self::TABLE_SECSTR_INFO, $row);
        if (false === $ret)
        {
            $error_code = $db_proxy->getErrorCode();
            $error_msg = $db_proxy->getErrorMsg();

            if ( $error_code == 1062) {
                throw new Exception('carpool.duplicate account already exists');
            }
            
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                                $error_code . ', error_msg: ' . $error_msg . ']');
        }

        switch($reason){
            case self::REASONTYPE_REG:
                //发短信
                if (CarpoolConfig::$debug) 
                {
                    return true;  
                }
                SmsPorxy::getInstance()->push_to_single($account, $sec_str);
            break;
            case self::REASONTYPE_PASSENGER_AUTH:
                $mail_profix = substr($account, strrpos($account, '@')+1);

                if(in_array($mail_profix,EmailConfig::$white_list))
                {

                    //发邮件， 这个时候不用给用户设置申请态， 因为他自己可以auth
                    EmailProxy::getInstance()->auth($account, CarpoolConfig::$domain."/rest/2.0/carpool/user?method=auth&type=$type&reason=$reason&account=$account&secstr=$sec_str&ctype=1&devuid=1");
                }
                else
                {

                    $ret = $db_proxy->update('user_info', array('and'=>
                        array(array('user_id' =>  array('=' => $arr_opt['user_id'])), 
                            array('user_type' => array('=' => self::USERTYPE_PASSENGER)),
                            array('status' =>  array('<>' => self::USERSTATUS_ACTIVE)),                                  
                        )), 'status='.self::USERSTATUS_CHECK); 

                    if (false === $ret) {
                        throw new Exception('carpool.internal update DB failed');
                    }
                }
                
                
            break;
        }    
        
        CLog::trace("get token succ [account: %s, secstr : %s]", $account, $sec_str);            
    }


    public function auth($arr_req, $arr_opt)
    {
        $account = $arr_req['account'];
        $secstr = $arr_req['secstr'];
        $ret = Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);
        if (false == $ret)
        {
            throw new Exception('carpool.param invalid account length [max_len: ' . 
                CarpoolConfig::USER_MAX_ACCOUNT_LENGTH . ']');
        }

        $user_id = 0;
        if(!self::check_str($account, $secstr, CarpoolConfig::CARPOOL_SECSTR_EMAIL_TIMEOUT, $user_id))
        {
            throw new Exception('carpool.secstr secstr wrong or timeout');
        }

        if(0 == $user_id)
        {
            return true;
        }
        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   


        $ret = $db_proxy->update('user_info', array('and'=>
            array(array('user_id' =>  array('=' => $user_id)), 
            array('user_type' => array('=' => self::USERTYPE_PASSENGER)),
                array('status' =>  array('<>' => self::USERSTATUS_ACTIVE)),                                  
        )), 'status='.self::USERSTATUS_ACTIVE);  
          
        
        CLog::trace("user auth succ [account: %s, secstr : %s]", $account, $sec_str);            
    }

    public function check_str($account, $secstr, $timeout, &$user_id = NULL)
    {
        if (!strpos($account, '@') && CarpoolConfig::$debug) {
            return true;  
        }

        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            return false;
        }   

        $condition = array(
            'and' => array(
                array(
                    'account' => array(
                        '=' => $account,
                    ),
                ),
                array(
                    'ctime' => array(
                        '>' => time(NULL) - $timeout,
                    ),
                ),
                array(
                    'secstr' => array(
                        '=' => $secstr,
                    ),
                ),
            ),
        );    

         
        $arr_response = $db_proxy->select(self::TABLE_SECSTR_INFO, array('id', 'user_id'), $condition);
          
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (1 != count($arr_response)) {
            return false;
        }       

        if(!is_null($user_id))
        {
            $user_id = intval($arr_response[0]['user_id']);
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
        if (!self::check_str($account, $secstr, CarpoolConfig::CARPOOL_SECSTR_PHONE_TIMEOUT)) {
            throw new Exception('carpool.secstr secstr error');
        }
        
        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
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
        
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('user_id','user_type', 'status'), $condition);
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
            $ret = $db_proxy->insert(self::TABLE_USER_INFO, $row);
            if (false === $ret)
            {
                $error_code = $db_proxy->getErrorCode();
                $error_msg = $db_proxy->getErrorMsg();
                throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                    $error_code . ', error_msg: ' . $error_msg . ']');
            }
            
            $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('user_id','user_type'), $condition);
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
        //只有司机可以report
        if($user_type == self::USERTYPE_PASSENGER)
        {
            return true;
        }
        // 2. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
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

            ),
        );
        
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('phone'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.param user_id not exist');
        }
        //司机没认证，不更新他的表
        if($arr_response[0]['status'] == self::USERSTATUS_INACTIVE)
        {
            return true;
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
        $ret = $db_proxy->insert('device_info', $row, $duplicate);
        if (false === $ret)
        {
            $error_code = $db_proxy->getErrorCode();
            $error_msg = $db_proxy->getErrorMsg();
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

    public function query($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        
        // 2. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
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

            ),
        );
        
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, '*', $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            throw new Exception('carpool.param user_id not exist');
        }
           
        $arr_return = array();

        $arr_return['name'] = $arr_response[0]['name'];
        $arr_return['status'] = $arr_response[0]['status'];
        $arr_return['ctime'] = intval($arr_response[0]['ctime']);
        $arr_return['type'] = intval($arr_response[0]['user_type']);
        $arr_return['phone'] = intval($arr_response[0]['phone']);

        if($user_type == self::USERTYPE_PASSENGER)
        {
            $arr_return['detail']['email'] = $arr_response[0]['email']; 
        }else
        {

            $arr_return['detail']['car_type'] = $arr_response[0]['car_type'];
            $arr_return['detail']['seat'] = $arr_response[0]['seat'];
            $arr_info = json_decode($arr_response[0]['detail'], true);            
        }
        $head_bucket = CarpoolConfig::$s3_bucket;

        $head_object = 'head_' . $user_id; 
        $now = time(NULL);  
        $uk = self::api_encode_uid($user_id);      

        //to do , 需要给头像地址
        $arr_return['head'] = 'http://'.CarpoolConfig::$domain."/rest/2.0/carpool/image?method=thumbnail&uk=$uk&timestamp=$now&sign="
            .hash_hmac('sha1', "$uk:$now", CarpoolConfig::$s3SK, false);

        CLog::trace("user query succ [account: %s]", $user_name);
                    
        return $arr_return;
    }

    public function modify($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        $user_type = $arr_req['user_type'] ;
        $name = '';
        $head = '';
        $update = '';
        if(!is_null($arr_opt['name']))
        {
            $name = $arr_opt['name'];
            $ret = Utils::check_string($name, 1, CarpoolConfig::USER_MAX_NAME_LENGTH);
            if (false == $ret) {
                throw new Exception('carpool.param invalid name length');
            }
            $update .= "name = '$name'";
        }
        if(!is_null($arr_opt['head']))
        {
            $head = $arr_opt['head'];
            $ret = Utils::check_string($head, 1, CarpoolConfig::USER_MAX_HEAD_LENGTH);
            if (false == $ret) {
                throw new Exception('carpool.param invalid name length');
            }
            

            $oss_sdk_service = new ALIOSS();
            $oss_sdk_service->set_host_name(CarpoolConfig::$s3_host);
            $head_bucket = CarpoolConfig::$s3_bucket;
            $head_object = 'head_' . $user_id;
            $upload_file_options = array(
                ALIOSS::OSS_CONTENT => $head,
                ALIOSS::OSS_LENGTH  => strlen($content), 
            );
            try{   
                $response = $oss_sdk_service->upload_file_by_content($head_bucket,$head_object,$upload_file_options);            
            }catch(Exception $ex){
                throw new Exception('carpool.internal upload s3 fail ;message :'
                    .$ex->getMessage() .'; file : '.$ex->getFile() .'; line : '.$ex->getLine());
            }
            if(!$response->isOk())
            {
                throw new Exception('carpool.internal upload s3 fail :'. $response->body);
            }

            if(!is_null($arr_opt['name']))
            {
                $update .= ',';
            }
            $head_bucket = CarpoolConfig::$s3_bucket;
            $head_object = '';
            $update .= "head_bucket = '$head_bucket', head_object = '$head_object'";

        }
        
        // 2. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $db_proxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }   

        
        $ret = $db_proxy->update('user_info', array('and'=>
            array(
                array('user_id' =>  
                    array('=' => $user_id)),                                                             
                )
            ), $update); 
        if (false === $ret) {
            throw new Exception('carpool.internal update DB failed');
        }

    }


    public static function api_encode_uid($user_id)
    {
        $sid = ($user_id & 0x0000ff00) << 16;
        $sid += (($user_id & 0xff000000) >> 8) & 0x00ff0000;
        $sid += ($user_id & 0x000000ff) << 8;
        $sid += ($user_id & 0x00ff0000) >> 16;
        $sid ^= 282335; 
        return $sid;
    }   
    public static function api_decode_uid($sid)
    {
        if (!is_int($sid) && !is_numeric($sid))
        {
            return false;
        }
        $sid ^= 282335;
        $user_id = ($sid & 0x00ff0000) << 8;
        $user_id += ($sid & 0x000000ff) << 16;
        $user_id += (($sid & 0xff000000) >> 16) & 0x0000ff00;
        $user_id += ($sid & 0x0000ff00) >> 8;
        return $user_id;
    }
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
