<?php

class PushService
{
    private static $instance = NULL;
    private $rpc = NULL;
    
    const TABLE_DEVICE_INFO = 'device_info';
    
    /**
     * @return PushService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new PushService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {
            
    }

    public function report($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        $user_id = $arr_req['user_id'];
        $user_name = $arr_req['user_name'];
        $devuid = $arr_req['devuid'];

        // client_id也是加密过的
        $client_id_encrypt = $arr_req['client_id'];
        $client_id = Ucrypt::rc4(CarpoolConfig::$passwdSK, base64_decode($client_id_encrypt));

        Utils::check_string($client_id, 1, CarpoolConfig::Push_MAX_CLIENT_ID_LENGTH);
        
        
        // 2. 检查可选参数合法性
        // none
        
        // 3. 记录client_id至数据库中
        $dbProxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        if (false === $dbProxy)
        {
            throw new Exception('carpool.internal connect to the DB failed');
        }
        
        $now = time();
        $devuid_sign = Utils::sign63($devuid);
        $row = array(
            'user_id'       => $user_id,
            'push_id'       => $client_id,
            'dev_id'        => $devuid,
            'dev_id_sign'   => $devuid_sign,
            'status'        => CarpoolConfig::$arrPushStatus['online'],
            'ctime'         => $now,
            'mtime'         => $now,
        );
        $duplicate_key = array(
            'push_id'   => $client_id,
            'status'    => CarpoolConfig::$arrPushStatus['online'],
        );
        $ret = $dbProxy->insert(self::TABLE_DEVICE_INFO, $row, $duplicate_key);
        if (false === $ret)
        {
            $error_code = $dbProxy->getErrorCode();
            $error_msg = $dbProxy->getErrorMsg();
            
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                                $error_code . ', error_msg: ' . $error_msg . ']');
        }
        
        CLog::trace("report succ [user_id: %s, user_name: %s]", $user_id, $user_name);
    }
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
