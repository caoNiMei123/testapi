<?php

/**
 * Config for phplib.
 **/

require_once('IDC.conf.php');

define('PROCESS_START_TIME', microtime(true)*1000);

define('LOG_TYPE', 'LOCAL_LOG');
define('LOG_LEVEL', 0x15);

define('HTDOCS_PATH', dirname(__FILE__) .'/../../');
define('LOCAL_LOG_PATH', HTDOCS_PATH .'/logs');

define('PUBLIC_PATH', HTDOCS_PATH .'/phplib');
define('PUBLIC_CONF_PATH', HTDOCS_PATH .'/conf/phplib');

define('TEMPLATE_PATH', HTDOCS_PATH .'/templates');
define('SMARTY_TEMPLATE_DIR', TEMPLATE_PATH .'/templates');
define('SMARTY_COMPILE_DIR', TEMPLATE_PATH .'/templates_c');
define('SMARTY_CONFIG_DIR', TEMPLATE_PATH .'/config');
define('SMARTY_CACHE_DIR', TEMPLATE_PATH .'/cache');
define('SMARTY_PLUGIN_DIR', TEMPLATE_PATH .'/plugins');
define('SMARTY_LEFT_DELIMITER', '{%');
define('SMARTY_RIGHT_DELIMITER', '%}');

class PublicLibManager
{
    /**
     * @var array
     */
    private $arrClasses;

    private static $instance;

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->arrClasses = array(
        'Action'                => PUBLIC_PATH .'/framework/Action.class.php',
        'ActionChain'           => PUBLIC_PATH .'/framework/ActionChain.class.php',
        'ActionController'      => PUBLIC_PATH .'/framework/ActionController.class.php',
        'ActionControllerConfig'=> PUBLIC_PATH .'/framework/ActionControllerConfig.class.php',
        'Context'               => PUBLIC_PATH .'/framework/Context.class.php',
        'Application'           => PUBLIC_PATH .'/framework/Application.class.php',
        
        'CLog'                  => PUBLIC_PATH .'/log/CLog.class.php',
        'NetComLog'             => PUBLIC_PATH .'/log/NetComLog.class.php',
        'NetComLogConfig'       => PUBLIC_CONF_PATH .'/NetComLogConfig.class.php',

        'ConnectionMan'         => PUBLIC_PATH .'/connectpool/ConnectionMan.class.php',
        
        'Utils'                 => PUBLIC_PATH .'/utils/Utils.class.php',
        'DistanceCompute'       => PUBLIC_PATH .'/utils/DistanceCompute.class.php',
        'NotifyConfig'           => PUBLIC_CONF_PATH.'/../notify/NotifyConfig.class.php',
        
        'FCrypt'                => PUBLIC_PATH .'/utils/FCrypt.class.php',
        'ResourceFactory'       => PUBLIC_PATH .'/utils/ResourceFactory.class.php',
        'Ucrypt'                => PUBLIC_PATH . '/utils/Ucrypt.class.php',
        'Bd_Crypt_Rc4'          => PUBLIC_PATH . '/utils/Bd_Crypt_Rc4.class.php',
        
        'bdTimer'               => PUBLIC_PATH .'/bdTimer/bdTimer.class.php',
        
        'HttpProxy'             => PUBLIC_PATH . '/http/HttpProxy.class.php',
        'MHttpProxy'            => PUBLIC_PATH . '/http/MHttpProxy.class.php',

        'EmailProxy'            => PUBLIC_PATH . '/email/EmailProxy.class.php',
        'EmailConfig'           => PUBLIC_CONF_PATH.'/../carpool/EmailConfig.class.php',
        'VersionConfig'           => PUBLIC_CONF_PATH.'/../carpool/VersionConfig.class.php',
        
        'SmsProxy'              => PUBLIC_PATH . '/sms/SmsProxy.class.php',
        
        'DBConfig'              => PUBLIC_CONF_PATH.'/DBConfig.class.php',
        'DBProxy'               => PUBLIC_PATH . '/db/DBProxy.class.php',

        'ALIOSS'              => PUBLIC_PATH . '/oss/sdk.class.php',
        
        'PushPorxy'             => PUBLIC_PATH . '/push/PushProxy.class.php',
        'PushProxyConfig'       => PUBLIC_CONF_PATH . '/PushProxyConfig.class.php',

        'IPCConfig'             => PUBLIC_CONF_PATH . '/IPCConfig.class.php',
        
        'IGeTui'                => PUBLIC_PATH . '/push/lib/IGt.Push.php',
        
        /*
         * 通常来说，不应该让上层模块配置在底层进行加载，但notify需要使用Carpool的配置，先临时用这个方法，
         * 后续考虑对共用配置抽取出来，放到phplib下即可
         */ 
        'CarpoolConfig'         => PUBLIC_CONF_PATH . '/../carpool/CarpoolConfig.class.php',
        );
    }

    /**
     * @return array
     */
    public function getPublicClassNames()
    {
        return $this->arrClasses;
    }

    public function RegisterMyClassName($className, $classPath)
    {
        $this->arrClasses[$className] = $classPath;
    }

    public function RegisterMyClasses(array $classes)
    {
        $this->arrClasses = array_merge($this->arrClasses, $classes);
    }
}

/**
 * Register user defined class into phplib's autoloader
 * @param string $className Name of user defined class
 * @param string $classPath File path of user defined class
 */
function RegisterMyClassName($className, $classPath)
{
    $PublicClassName = PublicLibManager::getInstance();
    $PublicClassName->RegisterMyClassName($className, $classPath);
}

/**
 * Register User defined classes into phplib's autoloader
 * @param array $classes    Class infos, use format: array(classname => class file path, ...)
 */
function RegisterMyClasses(array $classes)
{
    $PublicClassName = PublicLibManager::getInstance();
    $PublicClassName->RegisterMyClasses($classes);
}

function PublicLibAutoLoader($className)
{
    $PublicClassName = PublicLibManager::getInstance();
    $arrPublicClassName = $PublicClassName->getPublicClassNames();
    if (array_key_exists($className, $arrPublicClassName)) {        
        require_once($arrPublicClassName[$className]);
    } else {
        // Avoid to explictly require Smarty as it is a so big file
        $class = strtolower($className);
        if (!strncmp($class, 'smarty', 6)) {
            return;
        }
        $classFile = my_stream_resolve_include_path($className . '.class.php');
        if ($classFile) {           
            include_once($classFile);
        }
    }
}

// PHP 5.2.17 only
function my_stream_resolve_include_path($filename)
{
    $path = realpath($filename);
    if ($path) {
        return $path;
    }
    
    foreach (explode(PATH_SEPARATOR, ini_get('include_path')) as $root) {
        $path = realpath($root . '/' . $filename);
        if ($path) {
            return $path;
        }
    }

    return false;
}

spl_autoload_register('PublicLibAutoLoader');

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
