<?php

class UserService
{
    private static $instance = NULL;
    
    const TABLE_USER_INFO = 'user_info';
    const TABLE_SECSTR_INFO = 'secstr_info';
    const USERTYPE_DRIVER =1;
    const USERTYPE_PASSENGER=2;
    const USERTYPE_BOTH=3;
    
    const USERSTATUS_INIT = 0; //初始态
    const USERSTATUS_CHECK = 1;//乘客处于验证态;
    const USERSTATUS_AUTHORIZED = 2;//已经验证;
    const USERSTATUS_FAIL = 3;//审核失败;
    
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
    
    public function get_token($arr_req, $arr_opt)
    {
        $account = $arr_req['account'];
        Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);

        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        

        //目前reg 只支持手机， auth只支持邮箱
        
        Utils::is_valid_phone($account);
        //签名检查
        $raw = $arr_opt['sign'];
        $timestamp = $arr_req['timestamp'];
        Utils::check_null('sign', $raw);
        Utils::check_null('timestamp', $timestamp);
        $devuid = $arr_req['devuid'];
                
        $sign = hash_hmac('sha1', md5($account.$timestamp.$devuid),CarpoolConfig::$tokenSK );
        if($raw != $sign || (time(NULL) - $timestamp) > CarpoolConfig::TOKEN_TIMEOUT)
        {
            throw new Exception('carpool.param sign error , except: '.$sign);
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
        if (false === $arr_response || !is_array($arr_response) || 0 != count($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
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

    
        //发短信
        SmsPorxy::getInstance()->push_to_single($account, $sec_str);
        CLog::trace("get token succ [account: %s, secstr : %s]", $account, $sec_str);            
    }


   

    public function check_str($account, $secstr, $timeout, &$user_id = NULL)
    {
        return true;

        if (!strpos($account, '@') && CarpoolConfig::$debug) 
        {
            return true;  
        }

        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        

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
            throw new Exception('carpool.secstr secstr error');
        }
        if (1 != count($arr_response)) 
        {
            throw new Exception('carpool.secstr secstr error');
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
        Utils::check_string($account, 1, CarpoolConfig::USER_MAX_ACCOUNT_LENGTH);        
        $type = $arr_req['type'];   
        if (is_null($type)||($type != self::USERTYPE_DRIVER && $type !=self::USERTYPE_PASSENGER)) 
        {
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
        
        $condition = array(
            'and' => array(
                array(
                    'phone' => array(
                        '=' => $account,
                    ),
                ),
            ),
        );
        
        $arr_response = $db_proxy->select(self::TABLE_USER_INFO, array('user_id', 'status'), $condition);
        if (false === $arr_response || !is_array($arr_response))
        {
            throw new Exception('carpool.internal select from the DB failed');
        }
        if (0 == count($arr_response)) {
            
            $now = time(NULL);
            $row = array(               
                'phone'     => $account,
                'ctime'     => $now,
                'mtime'     => $now,
            );
            $ret = $db_proxy->insert(self::TABLE_USER_INFO, $row);
            if (false === $ret)
            {                
                throw new Exception('carpool.internal insert to the DB failed');
            }            
            $user_id = $db_proxy->getLastID();
            if(0 == $user_id)
            {
                throw new Exception('carpool.internal get user_id failed');
            }
        }
        else
        {      
            $user_id = intval($arr_response[0]['user_id']);
        }
                
        // 4. 设置cookie
        
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
        Utils::check_string($client_id, 1, 64);
        
        
        // 2. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
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
        $arr_return['ctime'] = intval($arr_response[0]['ctime']);
        $arr_return['phone'] = intval($arr_response[0]['phone']);
        $arr_return['sex'] = intval($arr_response[0]['sex']);
        $arr_return['car_type'] = $arr_response[0]['car_type'];
        $arr_return['car_engine_num'] = $arr_response[0]['car_engine_num'];
        $arr_return['car_num'] = $arr_response[0]['car_num'];
        $arr_return['status'] = intval($arr_response[0]['status']);
        
        $now = time(NULL);  
        $uk = self::api_encode_uid($user_id);      

        
        $arr_return['head'] = CarpoolConfig::$domain."/rest/2.0/carpool/image?method=thumbnail&ctype=1&devuid=1&uk=$uk&timestamp=$now&sign="
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
        
        $update = '';
        $driver_check = false;

        if(!is_null($arr_opt['name']))
        {
            $name = $arr_opt['name'];
            Utils::check_string($name, 1, CarpoolConfig::USER_MAX_NAME_LENGTH);            
            $update .= ",name = '$name'";
        }

        if(!is_null($arr_opt['sex']))
        {
            $sex = intval($arr_opt['sex']);
            Utils::check_int($sex, 0, 1);    
            $update .= ",sex = $sex";
        }

        if(!is_null($arr_opt['car_num']))
        {
                
            Utils::check_string($arr_opt['car_num'], 1, CarpoolConfig::USER_MAX_CAR_NUM_LENGTH);         
            $car_num = $arr_opt['car_num'];
            $update .= ",car_num = '$car_num'";
            $driver_check = true;
        }

        if(!is_null($arr_opt['car_type']))
        {
                
            Utils::check_string($arr_opt['car_type'], 1, CarpoolConfig::USER_MAX_CAR_TYPE_LENGTH);         
            $car_type = $arr_opt['car_type'];
            $update .= ",car_type = '$car_type'";
            $driver_check = true;
        }


        if(!is_null($arr_opt['car_engine_num']))
        {
            Utils::check_string($arr_opt['car_engine_num'], 1, CarpoolConfig::USER_MAX_CAR_ENGINE_NUM_LENGTH);
            $car_engine_num = $arr_opt['car_engine_num'];   
            $update .= ", car_engine_num = '$car_engine_num'";
            $driver_check = true;
        }
        Utils::check_string($update , 1);
        
        // 2. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        $update = substr($update, 1);

        //需要更新为未审核
        if($driver_check)
        {
            $arr_response = $db_proxy->select(self::TABLE_USER_INFO, 'status', array('and' => array(array('user_id' => array('=' => $user_id,),),),));
            if (false === $arr_response || !is_array($arr_response) || 0 == count($arr_response))
            {
                throw new Exception('carpool.internal select from the DB failed');
            }

            if(intval($arr_response[0]['status']) == self::USERSTATUS_AUTHORIZED)
            {
                throw new Exception('carpool.duplicate already authorized');
            }

            $update .= ", status = ". self::USERSTATUS_CHECK;   
        }

        
        $ret = $db_proxy->update('user_info', array('and'=>
            array(
                array('user_id' =>  
                    array('=' => $user_id)),                                                             
                )
            ), $update); 

        if (false === $ret || 1 !== $ret ) {
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
