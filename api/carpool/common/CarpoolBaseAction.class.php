<?php

class CarpoolBaseAction extends LogicBaseAction
{
    public function initial($initObject)
    {   
        parent::initial($initObject);
        
        $gen_param_str = '';
        
        // 通用参数检查
        // 1. check ctype
        $ctype = isset($this->requests['ctype'])?intval($this->requests['ctype']):1;
        $ret = Utils::check_int($ctype);
        if (false === $ret)
        {
            throw new Exception("carpool.param invalid ctype [ctype: " . $ctype . ']');
        }
        
        if (false === in_array($ctype, carpoolConfig::$arrClientType))
        {
            throw new Exception("carpool.param invalid ctype [ctype: " . $ctype . ']');
        }
        
        // 2. check devuid
        if (!isset($this->requests['devuid']))
        {
            throw new Exception("carpool.param devuid param not exist");
        }
        
        $devuid = $this->requests['devuid'];
        $ret = Utils::check_string($devuid, 1, 64);
        if (false === $ret)
        {
            throw new Exception("carpool.param invalid devuid [devuid: " . $devuid . ']');
        }
        
        $gen_param_str .= 'ctype: ' . $ctype . ', devuid: ' . $devuid;
        
        // 3. check logid
        if (isset($this->requests['logid']))
        {
            $logid = $this->requests['logid'];
            $ret = Utils::check_int($logid);
            if (false === $ret)
            {
                CLog::warning('invalid logid [logid: %s]', $logid);
            }
            else
            {
                $gen_param_str .= ', logid: ' . $logid;
            }
        }
        if (!CarpoolConfig::$debug) {
            unset($this->requests['user_id']);
            unset($this->requests['user_name']);
            unset($this->requests['user_type']);
        }
        // 4. 校验cookie字段
        
        // 此处只仅仅校验cookie中的字段，不做用户登录等验证，因为不同的api不一定都要求用户必须登录
        if (isset($_COOKIE['CPUINFO']))
        {
            $arr_uinfo = UserService::_decrypt_uinfo($_COOKIE['CPUINFO']);
            if (false !== $arr_uinfo && is_array($arr_uinfo))
            {
                if (isset($arr_uinfo['user_id']))
                {
                    $user_id = $arr_uinfo['user_id'];
                    $ret = Utils::check_int($user_id);
                    if (false === $ret)
                    {
                        CLog::warning('invalid user_id [user_id: %s]', $user_id);
                    }
                    else
                    {
                        $gen_param_str .= ', user_id: ' . $user_id;
                        $this->requests['user_id'] = $user_id;
                    }
                }
                
                if (isset($arr_uinfo['user_name']))
                {
                    $user_name = $arr_uinfo['user_name'];
                    $ret = Utils::check_string($user_name, 1, CarpoolConfig::USER_MAX_USERNAME_LENGTH);
                    if (false === $ret)
                    {
                        CLog::warning('invalid user_name [user_name: %s]', $user_name);
                    }
                    else
                    {
                        $gen_param_str .= ', user_name: ' . $user_name;
                        $this->requests['user_name'] = $user_name;
                    }
                }
                $this->requests['user_type'] = $arr_uinfo['user_type'];    
            }


        }
        
        // 打出所有参数日志
        CLog::debug('debug request param [request_param: %s]', 
        			print_r($this->requests, true));
        
        if (CarpoolConfig::$debug){
            return true;
        }
        // 5. check timestamp 
        $timestamp = $this->requests['timestamp'];
        $ret = Utils::check_int($timestamp);
        if (false === $ret) {
            throw new Exception("carpool.param invalid timestamp");
        }
        // 6. check sestoken

        if (!isset($this->requests['sstoken'])) {
            throw new Exception("carpool.param invalid sstoken");    
        }
        $raw = $this->requests['sstoken'];
        $sign = hash_hmac('sha1', md5($_COOKIE['CPUINFO'].$timestamp.$devuid),CarpoolConfig::$userSK );
        
        if (isset($this->requests['sstoken'])) {
            $sestoken = $this->requests['sstoken'];
            $ret = Utils::check_string($sestoken, 1, 64);
            if (false === $ret) {
                CLog::warning('invalid sestoken [sestoken: %s]', $sestoken);    
            }
            else {
                $gen_param_str .= ', sestoken: ' . $sestoken;
            }
        }
    
        CLog::trace('general params [%s]', $gen_param_str);
        
        return true;
    }
    public function exist($key , $message='carpool.param')
    {   
        if(!isset($this->requests[$key]))
        {
            throw new Exception("$message $key not exist");
        }

    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
