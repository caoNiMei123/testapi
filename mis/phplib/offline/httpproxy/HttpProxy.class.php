<?php

/**
 * @file HttpProxy.class.php
 * @brief Simple httprequest proxy (use curl send htpp request)
 *
 */
class HttpProxy
{
    const MAX_RETRY_TIMES = 3;  

    const CONNECTTIMEOUT  = 3;

    const TIMEOUT         = 6;
    
    const HTTP_GET        = 'GET';
    const HTTP_POST       = 'POST';
    const HTTP_PUT        = 'PUT';
    const HTTP_HEAD       = 'HEAD';
    const HTTP_DELETE     = 'HEAD';

    private static $instance = null; 

    protected $curl_handle;
    protected $curl_info;

    protected $request_url;
    protected $request_header;
    protected $request_url_param;
    protected $request_body_param;

    protected $http_method;

    protected $response_header;
    protected $response_body;
    protected $response_content_type;
    protected $response_http_code;

    protected $error_code;
    protected $error_msg;

    /**
     * @return HttpProxy
     */
    public static function getInstance()
    {
        if ( null === self::$instance ) {
            self::$instance = new HttpProxy();
        }
        return self::$instance;
    }

    /**
     * prevent external new()
     */
    private function __construct()
    {
        
    }

    /**
     * @return HttpProxy
     */
    public function initRequest( $request_url, $header = array() )
    {
        $this->request_url = $request_url;
        $this->setRequestHeader( $header );
        return self::$instance;
    }

    public function setRequestHeader( $header = array() )
    {
        if ( !is_array( $header ) ) {
            $this->request_header = array( $header );
        }
        $this->request_header = $header;
        return self::$instance;
    }

    public function setRequestUrl( $url = '' )
    {
        if ( $url !== '' ) {
            $this->request_url = $url;
            return self::$instance;
        } else {
            return false;
        }
    }
        
    public function get( $url_param = array() )
    {
        $this->http_method = self::HTTP_GET;
        $this->request_url_param = $url_param;
        return $this->send_request();
    }

    public function post( $body_param = array(), $url_param = array() )
    {
        $this->http_method = self::HTTP_POST;
        $this->request_url_param = $url_param;
        $this->request_body_param = $body_param;
        return $this->send_request();
    }

    public function send_request()
    {
        if ( $this->request_url === '' ) {
            return false;
        }
        
        $time = 0;
        $this->buildUrlQuery();
        $this->reset();
        while ( $time++ < self::MAX_RETRY_TIMES && empty( $this->response_body ) ) {
            $this->curl_handle = curl_init();
            $ch = $this->curl_handle;
            curl_setopt( $ch, CURLOPT_URL, $this->request_url);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->request_header );
            curl_setopt( $ch, CURLOPT_TIMEOUT, self::TIMEOUT );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, self::CONNECTTIMEOUT);
            curl_setopt( $ch, CURLOPT_NOSIGNAL, true );
            curl_setopt( $ch, CURLOPT_REFERER, $this->request_url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
            
            switch ( $this->http_method ) {
                case self::HTTP_POST:
                    curl_setopt( $ch, CURLOPT_POST, 1 );
                    curl_setopt( $ch, CURLOPT_POSTFIELDS, $this->request_body_param );
                    break;
                case self::HTTP_GET:
                    curl_setopt( $ch, CURLOPT_HTTPGET, 1);
                    break;
                default:
                    return false;
            }

            $result = curl_exec($ch);
            $this->response_body = $result;
            $this->response_http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            $this->response_content_type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
            $this->curl_info = curl_getinfo( $ch );
            $this->error_code = curl_errno( $ch );
            $this->error_msg = curl_error( $ch );
            if ( false === $result
                || $this->response_http_code >= 400 
                || $this->error_code > 0
            ) {
                curl_close($ch);
                continue;
            }
            curl_close($ch);
            if ( ( $this->response_http_code / 100 ) == 2 ) {
                break;
            } else {
                continue;
            }   
        }
        if ( ( $this->response_http_code / 100 ) == 2 ) {
            return $this->response_body;
        } else {
            return false;
        }
    }

    protected function buildUrlQuery()
    {
        if ( $this->request_url === '' ) {
            return false;
        }
        $queryString = '';
        if ( !isset( $this->request_url_param ) ) {
            return true;
        }
        if ( is_string( $this->request_url_param ) ) {
            $queryString = $this->request_url_param;    
        } elseif ( is_array( $this->request_url_param ) ) {
            $queryString = http_build_query( $this->request_url_param );
        } else {
            return false;
        }
        if ( strlen( $queryString ) === 0 ) {
            return true;
        }
        if ( strpos( $this->request_url, '?' ) === false ) {
            $this->request_url = $this->request_url . '?' . $queryString;
        } else {
            $this->request_url = $this->request_url . '&' . $queryString;
        } 
    }

    protected function reset()
    {
        $this->error_code = null;
        $this->error_msg = null;
        $this->curl_handle = null;
        $this->response_body = null;
    }
    
    public function getResponseBody()
    {
        return $this->response_body;
    }

    public function getResponseContentType()
    {
        return $this->response_content_type;
    }

    public function getErrorCode()
    {
        return $this->error_code;
    }

    public function getErrorMsg()
    {
        return $this->error_msg;
    }

    public function getHttpCode()
    {
        return $this->response_http_code;
    }
    
    public function getCurlInfo()
    {
        return $this->curl_info;
    }
}
