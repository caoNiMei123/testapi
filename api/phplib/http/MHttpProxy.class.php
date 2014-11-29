<?php

/**
 * @file MHttpProxy.class.php
 * @brief multiple httprequest proxy (use curl send http request)
 *
 */
class MHttpProxy
{
	private static $instance = null; 

	protected $arr_curl_object;
	
	/**
	 * @return HttpProxy
	 */
	public static function getInstance()
	{
        if ( null === self::$instance ) {
            self::$instance = new MHttpProxy();
		}
        return self::$instance;
    }

	private function __construct()
	{
	}
	
	public function mpost($curl_key, $request_url, $body_param = array(), $header = array())
	{
		if(isset($this->arr_curl_object[$curl_key])) {
			return true;
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $request_url);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 1 );
		curl_setopt( $ch, CURLOPT_NOSIGNAL, true );
		curl_setopt( $ch, CURLOPT_REFERER, $request_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $body_param );
		$this->arr_curl_object[$curl_key] = $ch;
		return true;
	}
	
	public function mexec(){
		$result = array();
		if (empty($this->arr_curl_object))
		 	return $result;
		
		$ch_multi = curl_multi_init();
		$added = count ( $this->arr_curl_object );
		foreach ($this->arr_curl_object as $ch) {
            curl_multi_add_handle($ch_multi, $ch);
		}
		
		$active = false;
		$tmpResult = array();
		do {
			do {
    			$status = curl_multi_exec($ch_multi, $active);
			} while ($status == CURLM_CALL_MULTI_PERFORM);
			while ( ($done = curl_multi_info_read ( $ch_multi )) ) {	
				if(! isset ( $tmpResult [intval($done['handle'])])) 
					$tmpResult[intval($done['handle'])] = $done;				
			}
			foreach ( $tmpResult as $done ) {
				$curl_key = array_search ( $done['handle'], $this->arr_curl_object, true );
				if(false === $curl_key || is_null($curl_key)) {
					continue;
				}
				if($done['result'] > 0){
					$result[$curl_key]['error_msg'] = $done['result'];
					$result[$curl_key]['error_code'] = -1;
					$result[$curl_key]['curl_info'] = '';
 					$result[$curl_key]['curl_body'] = '';
				}else{
					$result[$curl_key]['error_msg'] = curl_error( $done['handle'] );
					$result[$curl_key]['error_code'] = curl_errno( $done['handle'] );
					$result[$curl_key]['curl_info'] = curl_getinfo($done['handle'] );
					$result[$curl_key]['curl_body'] = curl_multi_getcontent ( $done['handle'] );
				}
				unset($this->arr_curl_object[$curl_key]);
				curl_multi_remove_handle ( $ch_multi, $done['handle'] );
				curl_close ( $done['handle'] );
			}      	
        } while ($active || count ($result) < $added || count($this->arr_curl_object) > 0);
		curl_multi_close($ch_multi);
		return $result;
	}
}
