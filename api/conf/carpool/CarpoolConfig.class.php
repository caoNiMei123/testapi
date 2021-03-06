<?php

class CarpoolConfig
{
    public static $arrExceptionReturnMap = array(
        // 通用错误码
        'carpool.unknown' => array('errno'=>CommonConst::EC_UNKNOWN, 'httpCode'=>'404'),
        'carpool.auth' => array('errno'=>CommonConst::EC_CM_NO_PERMISSION, 'httpCode'=>'403'),
        'carpool.internal' => array('errno'=>CommonConst::EC_CM_SERVICE_INVALID, 'httpCode'=>'500'),
        'carpool.param' => array('errno'=>CommonConst::EC_CM_PARAM_ERROR, 'httpCode'=>'400'),
        'carpool.not_found' => array('errno'=>CommonConst::EC_CM_DATA_NOTFOUND, 'httpCode'=>'404'),
        'carpool.duplicate' => array('errno'=>CommonConst::EC_CM_DUPLICATE, 'httpCode'=>'400'),
        'carpool.secstr' => array('errno'=>CommonConst::EC_CM_SECSTR_ERROR, 'httpCode'=>'400'),
        'carpool.order_status' => array('errno'=>CommonConst::EC_CM_ORDER_STATUS, 'httpCode'=>'400'),
        'carpool.invalid_driver' => array('errno'=>CommonConst::EC_CM_INVALID_DRIVER, 'httpCode'=>'400'),
        // UserService错误码
        'carpool.invalid_user' => array('errno'=>CommonConst::EC_USER_INVALID_USER, 'httpCode'=>'403'),
        'carpool.order_timeout' => array('errno'=>CommonConst::EC_CM_ORDER_TIMEOUT, 'httpCode'=>'400'),
        'carpool.order_canceled' => array('errno'=>CommonConst::EC_CM_ORDER_CANCELED, 'httpCode'=>'400'),
        'carpool.order_started' => array('errno'=>CommonConst::EC_CM_ORDER_STARTED, 'httpCode'=>'400'),
    );

    public static $userSK = 'A8ec24caf34ef7227cx6c67d29ffd3fb';
    
    public static $cookieSK = 'B7ec24caf34ef7a27cx6c67d29efd3fb';
    
    public static $tokenSK = 'Ocech4caf3eefjk27ck7c67d79e3d34g';

    public static $s3SK = 'C7ec24caf34ef7a27dx6c97d20e1dxea';
    
    // 请求的超时时间，单位: 秒
    public static $reqTimeout = 10;    
    
    public static $arrClientType = array(
        'web'   => 1,
        'pc'    => 2,
        'android' => 3,
        'ios'   => 4,
    );
    
    public static $debug = true; 

    public static $s3_bucket = 'real-pin'; 
    public static $s3_host = 'oss-cn-beijing-internal.aliyuncs.com';
    /*
     * UserService配置
     */
    // account最大长度
    const USER_MAX_ACCOUNT_LENGTH = 256;
    
    // passwd最大长度
    const USER_MAX_PASSWD_LENGTH = 32;

    // name最大长度
    const USER_MAX_NAME_LENGTH = 50;

    // head最大长度
    const USER_MAX_HEAD_LENGTH = 51200;//50k
   
    const TOKEN_TIMEOUT = 60;
   
    // cookie超时时间
    const USER_COOKIE_EXPIRE_TIME = 2592000;
    
    const USER_MAX_USERNAME_LENGTH = 32;

    const USER_MAX_CAR_NUM_LENGTH = 50;

    const USER_MAX_CAR_ENGINE_NUM_LENGTH = 50;

    const USER_MAX_CAR_TYPE_LENGTH = 50;

    const CARPOOL_ORDER_TIMEOUT = 120;

    const CARPOOL_SECSTR_PHONE_TIMEOUT = 60;

    const CARPOOL_SECSTR_EMAIL_TIMEOUT = 3600;

    const CARPOOL_PAGE_LIMIT = 100;

    const CARPOOL_IMAGE_TIMEOUT = 3600;

    const CARPOOL_DRIVER_REPORT_TIMEOUT = 1800;
    
    /*
     * PushService配置
     */
    const Push_MAX_CLIENT_ID_LENGTH = 64;
    
    public static $arrPushStatus = array(
        'online'    => 0,
        'offline'   => 1,
    );
    public static $arrPushType  = array(
        'create_order' =>1,
        'cancel_order' =>2,
        'accept_order' =>3,
        'driver_location' =>4,
        'order_info' =>5,
        'finish_order' =>6,
        'aboard_order' =>7,
    );

    const ORDER_PRICE_NORMAL = 100;
    const ORDER_PRICE_VIP = 200;
    //test
    //public static $domain = "http://10.26.74.23:8089";
    public static $domain = "http://182.92.164.183:8089";

}
