<?php

class ImageService
{
    private static $instance = NULL;
    

    
    /**
     * @return PushService
     */
    public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new ImageService();
        }
        
        return self::$instance;
    }

    protected function __construct()
    {
            
    }

    public function thumbnail($arr_req, $arr_opt)
    {
        CLog::trace("thumbnail succ [account: %s, type : %d, user_id : %d]", $account, $type,$user_id);
    }
    
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
