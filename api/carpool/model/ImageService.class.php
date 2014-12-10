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
        
        
        $uk = $arr_req['uk'] ;
        $timestamp = $arr_req['timestamp'] ;
        $sign = $arr_req['sign'] ;

        //需要判断timestamp有没有超时
        if($sign != hash_hmac('sha1', "$uk:$timestamp", CarpoolConfig::$s3SK, false))
        {
            throw new Exception('carpool.param check sign fail');
        }     
        $user_id = UserService::api_decode_uid($uk);
        $head_bucket = CarpoolConfig::$s3_bucket;   
        $head_object = 'head_' . $user_id; 
        $oss_sdk_service = new ALIOSS();
        $oss_sdk_service->set_host_name(CarpoolConfig::$s3_host);
        try{  
            $response = $oss_sdk_service->get_object($head_bucket,$head_object,$options); 
        }catch(Exception $ex){
            throw new Exception('carpool.internal get object fail ;message :'
                .$ex->getMessage() .'; file : '.$ex->getFile() .'; line : '.$ex->getLine());
        }
        
        if(!$response->isOk())
        {
            throw new Exception('carpool.internal upload s3 fail :'. $response->body);
        }
        CLog::trace("thumbnail succ [account: %s, type : %d, user_id : %d]", $account, $type,$user_id);

        return array(
            'img' => $response->body
        );

    }
    
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
