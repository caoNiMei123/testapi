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

        
        if($sign != hash_hmac('sha1', "$uk:$timestamp", CarpoolConfig::$s3SK, false))
        {
            throw new Exception('carpool.param check sign fail');
        }     
        if(time(NULL) - $timestamp > CarpoolConfig::CARPOOL_IMAGE_TIMEOUT)
        {
            throw new Exception('carpool.auth url timeout');
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
        
        return $response->body;

    }

    public function upload($arr_req, $arr_opt)
    {
        $user_id = $arr_req['user_id'];
        $file = $arr_req['file'];
        $type = $arr_req['type'];
        $head_bucket = '';
        $head_object = '';
        switch($type)
        {
        case 1:
            $head_bucket = CarpoolConfig::$s3_bucket;
            $head_object = 'head_' . $user_id;
            break;
        case 2:
            $head_bucket = CarpoolConfig::$s3_bucket;
            $head_object = 'driver_' . $user_id;
            break;
        case 3:
            $head_bucket = CarpoolConfig::$s3_bucket;
            $head_object = 'licence_' . $user_id;
            break;
        default:
            throw new Exception('carpool.param type illegal');
        }
        
        Utils::check_string($file, 1, CarpoolConfig::USER_MAX_HEAD_LENGTH);           

        $oss_sdk_service = new ALIOSS();
        $oss_sdk_service->set_host_name(CarpoolConfig::$s3_host);
        $upload_file_options = array(
            ALIOSS::OSS_CONTENT => $file,
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
    }
    
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
