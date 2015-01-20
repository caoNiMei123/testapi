<?php

class FeedbackService
{
    private static $instance = NULL;
    
    const TABLE_COMLAIN_INFO = 'complain_info';
    const FEEDBACKTYPE_MIN =1;    
    const FEEDBACKTYPE_SUGGEST =1;
    const FEEDBACKTYPE_BUG =2;
    const FEEDBACKTYPE_CONFUSE =3;
    const FEEDBACKTYPE_OTHER =4;
    const FEEDBACKTYPE_MAX =4;
    const FEEDBACKSTAUS_CREATE =0;
    const FEEDBACKSTAUS_DONE =1;
    
    /**
     * @return PushService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new FeedbackService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {
            
    }

    public function create($arr_req, $arr_opt)
    {
        // 1. 检查必选参数合法性
        // 检查account
        $type = $arr_req['type'];
        $detail = $arr_req['detail'];
        $user_name = $arr_req['user_name'] ;
        $user_id = $arr_req['user_id'] ;
        if($type < self::FEEDBACKTYPE_MIN || $type > self::FEEDBACKTYPE_MAX )
        {
            throw new Exception('carpool.param invalid type');
        }
        Utils::check_string($detail, 1, 1024); 

        
        $now = time(NULL);
        $row = array(         
            'user_id'     => $user_id,      
            'phone'     => $user_name,
            'type' => $type,
            'content' => $detail,                
            'ctime'     => $now,
            'mtime'     => $now,
            'status'    => self::FEEDBACKSTAUS_CREATE,
        );
        
        
        // 3. 访问数据库
        $db_proxy = DBProxy::getInstance()->setDB(DBConfig::$carpoolDB);
        
        $ret = $db_proxy->insert(self::TABLE_COMLAIN_INFO, $row);
        if (false === $ret)
        {
            $error_code = $db_proxy->getErrorCode();
            $error_msg = $db_proxy->getErrorMsg();     
            
            throw new Exception('carpool.internal insert to the DB failed [error_code: ' . 
                                $error_code . ', error_msg: ' . $error_msg . ']');
        }
        
        CLog::trace("feedback succ [account: %s, user_id : %d]", $user_name,$user_id);
    }    
    
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
