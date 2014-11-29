<?php

class CommonConst
{  

    /** success */
    const SUCCESS                     = 0;
    /** uknown error */
    const EC_UNKNOWN                  = -1;

    // 通用错误码
    const EC_CM_API_UNSUPPORTED        = 1;
    const EC_CM_NO_PERMISSION          = 2;
    const EC_CM_SERVICE_INVALID        = 3;
    const EC_CM_PARAM_ERROR            = 4;
    const EC_CM_DATA_NOTFOUND          = 5;
    const EC_CM_DUPLICATE          = 6;
    const EC_CM_SECSTR_ERROR          = 7;
    const EC_CM_ORDER_STATUS          = 8;
    const EC_CM_INVALID_DRIVER          = 9;

    
    // UserService错误码
    const EC_USER_INVALID_USER         = 100; // 无效的用户
    
    // DeviceService错误码
    const EC_USER_BIND				   = 200; // 已绑定

    
    static $errorDescs = array(
        self::SUCCESS                      => 'success',
        self::EC_UNKNOWN                   => 'uknown error',
        self::EC_CM_API_UNSUPPORTED        => 'api not support',
        self::EC_CM_NO_PERMISSION          => 'no permission',
        self::EC_CM_PARAM_ERROR            => 'param error',
        self::EC_CM_SERVICE_INVALID        => 'backend service is not available',
        self::EC_CM_DATA_NOTFOUND		   => 'data not found',        
        self::EC_USER_INVALID_USER		   => 'invalid user',        
        self::EC_CM_DUPLICATE		       => 'duplicate',        
        self::EC_USER_BIND				   => 'already bind',
        self::EC_CM_SECSTR_ERROR           => 'secstr error',
        self::EC_CM_ORDER_STATUS           => 'order status now allow',
        self::EC_CM_INVALID_DRIVER         => 'invalid driver',
    );

    static $tipMsgs = array(
        /*
        self::EC_API_INVALID_PASSWD        => '密码错误，请重新输入',
        self::EC_API_USER_NOT_EXIST        => '帐号不存在',
        self::EC_API_USER_NOT_ACTIVED      => '帐号尚未激活，请前往邮箱激活',
        self::EC_API_USER_FORBID_TEMP      => '操作频繁，请稍候再试',
        self::EC_API_USER_FORBID_FOREVER   => '对不起，您的帐号无法登录，请联系客服',
        */
    );

    public static function getErrorDesc($errno)
    {
        if (isset(self::$errorDescs[$errno])) {
            return self::$errorDescs[$errno];
        } else {
            return 'unknown error';
        }
    }

    public static function getTipMessage($errno)
    {
        if (isset(self::$tipMsgs[$errno])) {
            return self::$tipMsgs[$errno];
        } else {
            return '';
        }
    }
}


/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
