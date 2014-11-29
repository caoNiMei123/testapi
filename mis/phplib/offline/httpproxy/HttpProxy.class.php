<?php
class HttpProxy
{
	const SUCCESS = 0;				
	const errUrlInvalid = 1;		
	const errServiceInvalid = 2;	
	const errHttpTimeout = 3;		
	const errTooManyRedirects = 4;	
	const errTooLargeResponse = 5;	
	const errResponseErrorPage = 6;	
	const errNoResponse = 7;		
	const errNoResponseBody = 8;	
	const errOtherEror = 9;			
	protected $curl;
	protected $curl_info;
	protected $curl_options = null;	//curl options
	protected $max_response_size;	//max response body size
	protected $errno;
	protected $errmsg;
	protected $header;
	protected $body;
	protected $body_len;
	private static $instance = null;


	public static function multiExec($chList){

		$result = array();
	    if (count ( $chList ) === 0)
		 	return $result;
		//¿¿curl¿¿¿
		if(count ( $chList ) === 1){
			$content = curl_exec($chList[0]);
			$errno = curl_errno($chList[0]);
			if($errno != 0){
				$result[0]['code'] = 0;  
				$result[0]['body'] = '';  
				$result[0]['errno'] = $errno;
			}else {
				$curlinfo = curl_getinfo($chList[0] ); 
				$result[0]['code'] = $curlinfo['http_code'];  
				$result[0]['body'] = $content;  
				$result[0]['errno'] = 0;
			}
			return $result;

		}
		$chMulti = curl_multi_init();
		$added = count ( $chList );
		foreach ($chList as $i => $ch) 
            		curl_multi_add_handle($chMulti, $ch);
		$active = false;
		$tmpResult = array();
	    do {
		    $status = curl_multi_exec($chMulti, $active);
		} while ($status == CURLM_CALL_MULTI_PERFORM);
		do {
		    if(curl_multi_select($chMulti) != -1){
				do {
					$status = curl_multi_exec($chMulti, $active);
				}while ($status == CURLM_CALL_MULTI_PERFORM);
			
				while ( $done = curl_multi_info_read ( $chMulti ) ) {	
					if(! isset ( $tmpResult [( int ) $done ['handle']])) 
						$tmpResult[( int ) $done ['handle']] = $done;				
				}
			}

		}while($active);
		foreach ( $tmpResult as $pkey => $done ) {
			$key = array_search ( $done ['handle'], $chList, true );
			if($done['result'] > 0){
				$result[$key]['errno'] = $done['result'] ;
				$result[$key]['code'] = 0;
 				$result[$key]['body'] = '';
			}else{
				$curlinfo = curl_getinfo($done['handle'] );
				$result[$key]['errno'] = $done['result'] ;
				$result[$key]['code'] = $curlinfo['http_code'];
				$result[$key]['body'] = curl_multi_getcontent ( $done ['handle'] );
			}	
			curl_multi_remove_handle ( $chMulti, $done ['handle'] );
			curl_close ( $done ['handle'] );
		}
		curl_multi_close($chMulti);
		ksort ( $result, SORT_NUMERIC );
		return $result;

	}

	

	protected function __construct($options = null)
	{
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$this->reset();

		$this->curl_options = array(
				'follow_location' => false,
				'max_redirs' => 4,
				'max_redirs_post'=>1,
				'conn_retry' => 3,
				'conn_timeout' => 10000,
				'timeout' => 30000,
				'user_agent' => $user_agent,
				'referer' => $referer,
				'encoding' => '',
				'httpheader'=>array(),
				'max_response_size' => 51200000000,	//default is 500k
				);

		$this->setOptions($options);
		
	}


	public function setHeader($headerArray)
	{
		$this->curl_options['httpheader']=$headerArray;
	}


	public static function getInstance($options = null)
	{
		if( self::$instance === null )
		{
			self::$instance = new HttpProxy($options);
		}
		else
		{
			self::$instance->setOptions($options);
		}
		return self::$instance;
	}

	public static function onResponseHeader($curl, $header)
	{
		$proxy = HttpProxy::getInstance();
		$proxy->header .= $header;

		$trimmed = trim($header);
		if( preg_match('/^Content-Length: (\d+)$/i', $trimmed, $matches) )
		{ 
			$content_length = $matches[1];
			if( $content_length > $proxy->max_response_size )
			{
				$proxy->body_len = $content_length;
				return 0;
			}
		} 

		return strlen($header);
	}

	public static function onResponseData($curl, $data)
	{
		$proxy = HttpProxy::getInstance();
		
		$chunck_len = strlen($data);
		$proxy->body .= $data;
		$proxy->body_len += $chunck_len;
		
		if( $proxy->body_len <= $proxy->max_response_size )
		{
			return $chunck_len;
		}
		else
		{
			return 0;
		}
	}

	public function setOptions($options)
	{
		if( is_array($options) )
		{
			//$options + $default_options results in an assoc array with overlaps
			//deferring to the value in $options
			$this->curl_options = $options + $this->curl_options;
		}

		$this->max_response_size = $this->curl_options['max_response_size'];
	}

	public function get($url, $cookie = array())
	{
		$this->reset();

		extract($this->curl_options);

		$curl = curl_init();
		if( $max_redirs < 1 )
		{
			$max_redirs = 1;
		}
		$curl_opts = array( CURLOPT_URL => $url,
							//CURLOPT_CONNECTTIMEOUT_MS => $conn_timeout,
							//CURLOPT_TIMEOUT_MS => $timeout,
							CURLOPT_USERAGENT => $user_agent,
							CURLOPT_REFERER => $referer,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_NOSIGNAL => true,
							CURLOPT_HEADER => false,
							CURLOPT_FOLLOWLOCATION => $follow_location,
							CURLOPT_MAXREDIRS => $max_redirs,
							CURLOPT_ENCODING => $encoding,
							CURLOPT_WRITEFUNCTION => 'HttpProxy::onResponseData',
							CURLOPT_HEADERFUNCTION => 'HttpProxy::onResponseHeader',
						);
		if(count($httpheader)!==0)
		{
			$curl_opts[CURLOPT_HTTPHEADER]=$httpheader;
		}


		//mod 20100106: ÐÞ¸´µÍ°æ±¾CURL²»Ö§³ÖCURLOPT_TIMEOUT_MSµÄbug;
		if ( defined('CURLOPT_TIMEOUT_MS') && defined('CURLOPT_CONNECTTIMEOUT_MS') ) {
			$curl_opts[CURLOPT_TIMEOUT_MS] = max($timeout,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT_MS] = max($conn_timeout,1);
		}else {
			$curl_opts[CURLOPT_TIMEOUT] = max($timeout/1000,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT] = max($conn_timeout/1000,1);
		}

		if( is_array($cookie) && count($cookie) > 0 )
		{
			$cookie_str = '';
			foreach( $cookie as $key => $value )
			{
				$cookie_str .= "$key=$value; ";
			}
			$curl_opts[CURLOPT_COOKIE] = $cookie_str;
		}

		curl_setopt_array($curl, $curl_opts);

		curl_exec($curl);

		$errno = curl_errno($curl);
		$errmsg = curl_error($curl);
		$this->curl_info = curl_getinfo($curl);

		curl_close($curl);

		if( $this->check_http_response($url, $errno, $errmsg) )
		{
			return $this->body;
		}
		return false;
	}

	public function post($url, $params, $cookie = array())
	{
		$this->reset();

		extract($this->curl_options);

		$curl = curl_init();
		if( $max_redirs_post < 1 )
		{
			$max_redirs_post = 1;
		}

		$curl_opts = array( CURLOPT_URL => $url,
							//CURLOPT_CONNECTTIMEOUT_MS => $conn_timeout,
							//CURLOPT_TIMEOUT_MS => $timeout,
							CURLOPT_USERAGENT => $user_agent,
							CURLOPT_REFERER => $referer,
							CURLOPT_NOSIGNAL => true,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_HEADER => false,
							CURLOPT_ENCODING => $encoding,
							CURLOPT_WRITEFUNCTION => 'HttpProxy::onResponseData',
							CURLOPT_HEADERFUNCTION => 'HttpProxy::onResponseHeader',
							CURLOPT_HTTPHEADER => $httpheader,
							);
		
		//mod 20100106: ÐÞ¸´µÍ°æ±¾CURL²»Ö§³ÖCURLOPT_TIMEOUT_MSµÄbug;
		if ( defined('CURLOPT_TIMEOUT_MS') && defined('CURLOPT_CONNECTTIMEOUT_MS') ) {
			$curl_opts[CURLOPT_TIMEOUT_MS] = max($timeout,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT_MS] = max($conn_timeout,1);
		}else {
			$curl_opts[CURLOPT_TIMEOUT] = max($timeout/1000,1);
			$curl_opts[CURLOPT_CONNECTTIMEOUT] = max($conn_timeout/1000,1);
		}

		if( is_array($cookie) && count($cookie) > 0 )
		{
			$cookie_str = '';
			foreach( $cookie as $key => $value )
			{
				$cookie_str .= "$key=$value; ";
			}
			$curl_opts[CURLOPT_COOKIE] = $cookie_str;
		}

		curl_setopt_array($curl, $curl_opts);

		$last_url   = $url;
		$redirects  = 0;
		$retries    = 0;

		if(is_array($params))
			$post_str = http_build_query($params);
		else
			$post_str = $params;

		if( $max_redirs_post == 1 )
		{
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_str);
			curl_exec($curl);
			$errno = curl_errno($curl);
			$errmsg = curl_error($curl);
			$this->curl_info = curl_getinfo($curl);
		}
		else
		{
			$start_time = microtime(true);
			for( $attempt = 0; $attempt < $max_redirs_post; $attempt++ )
			{
				
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_str);
				curl_exec($curl);
				$errno = curl_errno($curl);
				$errmsg = curl_error($curl);
				$this->curl_info = curl_getinfo($curl);			
				$curl_info = $this->curl_info;
				
				//Remove any HTTP 100 headers
				if( ($curl_info['http_code'] == 301 ||
					 $curl_info['http_code'] == 302 ||
					 $curl_info['http_code'] == 307) &&
					preg_match('/Location: ([^\r\n]+)\r\n/si', $this->header, $matches) )
				{
					$new_url = $matches[1];

					//if $new_url is relative path, prefix with domain name
					if( !preg_match('/^http(|s):\/\//', $new_url) &&
						preg_match('/^(http(?:|s):\/\/.*?)\//', $url, $matches) )
					{
						$new_url = $matches[1] . '/' . $new_url;
					}
					$last_url = $new_url;
					curl_setopt($curl, CURLOPT_URL, $new_url);

					//reduce the timeout, but keep it at least 1 or we wind up with an infinite timeout
					
					if ( defined('CURLOPT_TIMEOUT_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUT_MS, max($start_time + $timeout - microtime(true), 1)); 
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max(($start_time + $timeout - microtime(true))/1000, 1)); 
					}
					++$redirects;
				}
				elseif( $conn_retry && strlen($this->header) == 0 )
				{
					//probably a connection failure...if we have time, try again...
					$time_left = $start_time + $timeout - microtime(true);
					if( $time_left < 1 )
					{
						break;
					}
					// ok, we've got some time, let's retry
					curl_setopt($curl, CURLOPT_URL, $last_url);
					if ( defined('CURLOPT_TIMEOUT_MS') ) {
						curl_setopt($curl, CURLOPT_TIMEOUT_MS, max($time_left,1));
					}else {
						curl_setopt($curl, CURLOPT_TIMEOUT, max($time_left/1000,1));
					}
					++$retries;
				}
				else
				{
					break; // we have a good response here
				}
			}
		}

		curl_close($curl);

		if( $this->check_http_response($url, $errno, $errmsg) )
		{
			return $this->body;
		}

		return false;
	}

	public function content_type()
	{
		//take content-type field into account first
		if( !empty($this->curl_info['content_type']) &&
			preg_match('#charset=([^;]+)#i', $this->curl_info['content_type'], $matches) )
		{
			return $matches[1];
		}

		return false;
	}

	public function body()
	{
		return $this->body;
	}

	public function cookie()
	{
		if( empty($this->header) )
		{
			return array();
		}

		$new_cookie = array();

		$headers = explode("\n", $this->header);
		foreach( $headers as $item )
		{
			if( strncasecmp($item, 'Set-Cookie:', 11) === 0 )
			{
				$cookiestr = trim(substr($item, 11, -1));
				$cookie = explode(';', $cookiestr);
				$cookie = explode('=', $cookie[0]);

				$cookiename = trim(array_shift($cookie));
				$new_cookie[$cookiename] = trim(implode('=', $cookie));
			}
		}

		return $new_cookie;
	}

	public function errno()
	{
		return $this->errno;
	}

	public function errmsg()
	{
		return $this->errmsg;
	}

	private function check_http_response($url, $errno, $errmsg)
	{
		$url = htmlspecialchars($url, ENT_QUOTES);

		$http_code = $this->curl_info['http_code'];

		if( $errno == CURLE_URL_MALFORMAT ||
			$errno == CURLE_COULDNT_RESOLVE_HOST )
		{
			$this->errno = self::errUrlInvalid;
			$this->errmsg = "The URL $url is not valid.";
		}
		elseif( $errno == CURLE_COULDNT_CONNECT )
		{
			$this->errno = self::errServiceInvalid;
			$this->errmsg = "Service for URL[$url] is invalid now, errno[$errno] errmsg[$errmsg]";
		}
		elseif( $errno == 28/*CURLE_OPERATION_TIMEDOUT*/ )
		{
			$this->errno = self::errHttpTimeout;
			$this->errmsg = "Request for $url timeout: $errmsg";
		}
		elseif( $errno == CURLE_TOO_MANY_REDIRECTS ||
			$http_code == 301 || $http_code == 302 || $http_code == 307 )
		{
			//$errno == CURLE_OK can only indicate that the response is received, but it may
			//also be an error page or empty page, so we also need more checking when $errno == CURLE_OK
			$this->errno = self::errTooManyRedirects;
			$this->errmsg = "Request for $url caused too many redirections.";
		}
		elseif( $http_code >= 400 )
		{
			$this->errno = self::errResponseErrorPage;
			$this->errmsg = "Received HTTP error code $http_code while loading $url";
		}
		elseif( $this->body_len > $this->max_response_size )
		{
			$this->errno = self::errTooLargeResponse;
			$this->errmsg = "Response body for $url has at least {$this->body_len} bytes, " .
							"which has exceed the max response size[{$this->max_response_size}]";
		}
		elseif( $errno != CURLE_OK )
		{
			if( $this->body_len == 0 )
			{
				if( $http_code )
				{
					$this->errno = self::errNoResponseBody;
					$this->errmsg = "Request for $url returns HTTP code $http_code and no data.";
				}
				else
				{
					$this->errno = self::errNoResponse;
					$this->errmsg = "The URL $url has no response.";
				}
			}
			else
			{
				$this->errno = self::errOtherEror;
				$this->errmsg = "Request for $url failed, errno[$errno] errmsg[$errmsg]";
			}
		}
		else
		{
			$this->errno = self::SUCCESS;
			$this->errmsg = '';
			return true;
		}

		return false;
	}

	private function reset()
	{
		$this->errno = self::SUCCESS;
		$this->errmsg = '';
		$this->header = '';
		$this->body = '';
		$this->body_len = 0;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */
?>
