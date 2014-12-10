<?php

/**
 * Some Util functions, mainly for string operations.
 */
class Utils
{
	/**
	 * check if the first arg starts with the second arg
	 *
	 * @param string $str		the string to search in
	 * @param string $needle	the string to be searched
	 * @return bool	true or false
	 **/
	public static function starts_with($str, $needle)
	{
		$pos = stripos($str, $needle);
		return $pos === 0;
	}

	/**
	 * check if the first arg ends with the second arg
	 *
	 * @param string $str		the string to search in
	 * @param string $needle	the string to be searched
	 * @return bool	true or false
	 **/
	public static function ends_with($str, $needle)
	{
		$pos = stripos($str, $needle);
		if( $pos === false ) {
			return false;
		}
		return ($pos + strlen($needle) == strlen($str));
	}

	/**
	 * undoes any magic quote slashing from an array, like the $_GET, $_POST, $_COOKIE
	 *
	 * @param array	$val	Array to be noslashing
	 * @return array The array with all of the values in it noslashed
	 **/
	public static function noslashes_recursive($val)
	{
		// @add by linxg, 在5.4中get_magic_quotes_gpc返回false，则注释掉以下分支
		//if (get_magic_quotes_gpc()) {
			//$val = self::stripslashes_recursive($val);
		//}
		return $val;
	}

	public static function stripslashes_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'stripslashes_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::stripslashes_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return stripslashes($var);
		} else {
			return $var;
		}
	}

	/**
	 * Convert string or array to requested character encoding
	 *
	 * @param mix $var	variable to be converted
	 * @param string $in_charset	The input charset.
	 * @param string $out_charset	The output charset
	 * @return mix	The array with all of the values in it noslashed
	 * @see http://cn2.php.net/manual/en/function.iconv.php
	 **/
	public static function iconv_recursive($var, $in_charset = 'UTF-8', $out_charset = 'GBK')
	{
		if (is_array($var)) {
			$rvar = array();
			foreach ($var as $key => $val) {
				$rvar[$key] = self::iconv_recursive($val, $in_charset, $out_charset);
			}
			return $rvar;
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::iconv_recursive($val, $in_charset, $out_charset);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return iconv($in_charset, $out_charset, $var);
		} else {
			return $var;
		}
	}

	/**
	 * Check if the text is gbk encoding
	 *
	 * @param string $str	text to be check
	 * @return bool
	 **/
	public static function is_gbk($str)
	{
		return preg_match('%^(?:[\x81-\xFE]([\x40-\x7E]|[\x80-\xFE]))*$%xs', $str);
	}

	/**
	 * Check if the text is utf8 encoding
	 *
	 * @param string $str	text to be check
	 * @return bool Returns true if input string is utf8, or false otherwise
	 **/
	public static function is_utf8($str)
	{
		return preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]'.	// ASCII
			'| [\xC2-\xDF][\x80-\xBF]'.				//non-overlong 2-byte
			'| \xE0[\xA0-\xBF][\x80-\xBF]'.			//excluding overlongs
			'| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.	//straight 3-byte
			'| \xED[\x80-\x9F][\x80-\xBF]'.			//excluding surrogates
			'| \xF0[\x90-\xBF][\x80-\xBF]{2}'.		//planes 1-3
			'| [\xF1-\xF3][\x80-\xBF]{3}'.			//planes 4-15
			'| \xF4[\x80-\x8F][\x80-\xBF]{2}'.		//plane 16
			')*$%xs', $str);
	}

	public static function txt2html($text)
	{
		return htmlspecialchars($text, ENT_QUOTES, 'GB2312');
	}

	/**
	 * Escapes text to make it safe to display in html.
	 * FE may use it in Javascript, we also escape the QUOTES
	 *
	 * @param string $str	text to be escaped
	 * @return string	escaped string in gbk
	 **/
	public static function escape_html_entities($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, 'GB2312');
	}

	/**
	 * Escapes text to make it safe to use with Javascript
	 *
	 * It is usable as, e.g.:
	 *  echo '<script>alert(\'begin'.escape_js_quotes($mid_part).'end\');</script>';
	 * OR
	 *  echo '<tag onclick="alert(\'begin'.escape_js_quotes($mid_part).'end\');">';
	 * Notice that this function happily works in both cases; i.e. you don't need:
	 *  echo '<tag onclick="alert(\'begin'.txt2html_old(escape_js_quotes($mid_part)).'end\');">';
	 * That would also work but is not necessary.
	 *
	 * @param string $str	text to be escaped
	 * @param bool $quotes	whether should wrap in quotes
	 * @return string
	 **/
	public static function escape_js_quotes($str, $quotes = false)
	{
		$str = strtr($str, array('\\'	=> '\\\\',
			"\n"	=> '\\n',
			"\r"	=> '\\r',
			'"'	=> '\\x22',
			'\''	=> '\\\'',
			'<'	=> '\\x3c',
			'>'	=> '\\x3e',
			'&'	=> '\\x26'));

		return $quotes ? '"'. $str . '"' : $str;
	}

	public static function escape_js_in_quotes($str, $quotes = false)
	{
		$str = strtr($str, array('\\"'	=> '\\&quot;',
			'"'	=> '\'',
			'\''	=> '\\\'',
		));

		return $quotes ? '"'. $str . '"' : $str;
	}

	/**
	 * Redirect to the specified page
	 *
	 * @param string $url	the specified page's url
	 * @param bool $top_redirect	Whether need to redirect the top page frame
	 **/
	public static function redirect($url, $top_redirect = true)
	{
		exit();
	}

	/**
	 * Get current page's real url
	 * 
	 * @return string
	 **/
	public static function current_url()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$scheme = 'https';
		}

		return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Whether current request is https request
	 * 
	 * @return bool
	 */
	public static function is_https_request()
	{
		$scheme = 'http';
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
		} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$scheme = 'https';
		}
		return ($scheme == 'https');
	}
	
	/**
	 * Remove specified params from the query parameters of the url
	 * 
	 * @param string $url
	 * @param array|string $params
	 * @return string
	 */
	public static function remove_queries_from_url($url, $params)
	{
		if (is_string($params)) {
			$params = explode(',', $params);
		}
		
		$parts = parse_url($url);
		if ($parts === false || empty($parts['query'])) {
			return $url;
		}
		
		$get = array();
		parse_str($parts['query'], $get);
		foreach ($params as $key) {
			unset($get[$key]);
		}
		
		$url = $parts['scheme'] . '://' . $parts['host'];
		if (isset($parts['port'])) {
			$url .= ':' . $parts['host'];
		}
		
		$url .= $parts['path'];
		if (!empty($get)) {
			$url .= '?' . http_build_query($get);
		}
		
		if (!empty($parts['fragment'])) {
			$url .= '#' . $parts['fragment'];
		}
		
		return $url;
	}

	/**
	 * Converts charactors in the string to upper case
	 *
	 * @param string $str string to be convert
	 * @return string
	 * @author zhujt
	 **/
	public static function strtoupper($str)
	{
		$uppers =
			array('A','B','C','D','E','F','G','H','I','J','K','L','M','N',
				'O', 'P','Q','R','S','T','U','V','W','X','Y','Z');
		$lowers =
			array('a','b','c','d','e','f','g','h','i','j','k','l','m','n',
				'o','p','q','r','s','t','u','v','w','x','y','z');
		return str_replace($lowers, $uppers, $str);
	}

	/**
	 * Converts charactors in the string to lower case
	 *
	 * @param string $str	string to be convert
	 * @return string
	 **/
	public static function strtolower($str)
	{
		$uppers =
			array('A','B','C','D','E','F','G','H','I','J','K','L','M','N',
				'O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$lowers =
			array('a','b','c','d','e','f','g','h','i','j','k','l','m','n',
				'o','p','q','r','s','t','u','v','w','x','y','z');
		return str_replace($uppers, $lowers, $str);
	}

	/**
	 * Urlencode a variable recursively, array keys and object property names
	 * will not be encoded, so you would better use ASCII to define the array
	 * key name or object property name.
	 *
	 * @param mixed $var
	 * @return  mixed, with the same variable type
	 **/
	public static function urlencode_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'urlencode_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::urlencode_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return urlencode($var);
		} else {
			return $var;
		}
	}

	/**
	 * Urldecode a variable recursively, array keys and object property
	 * names will not be decoded, so you would better use ASCII to define
	 * the array key name or object property name.
	 *
	 * @param mixed $var
	 * @return  mixed, with the same variable type
	 **/
	public static function urldecode_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'urldecode_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::urldecode_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return urldecode($var);
		} else {
			return $var;
		}
	}

	/**
	 * Encode a string according to the RFC3986
	 * @param string $s
	 * @return string
	 */
	public static function urlencode3986($var)
	{
		return str_replace('%7E', '~', rawurlencode($var));
	}

	/**
	 * Decode a string according to RFC3986.
	 * Also correctly decodes RFC1738 urls.
	 * @param string $s
	 */
	public static function urldecode3986($var)
	{
		return rawurldecode($var);
	}

	/**
	 * Urlencode a variable recursively according to the RFC3986, array keys
	 * and object property names will not be encoded, so you would better use
	 * ASCII to define the array key name or object property name.
	 *
	 * @param mixed $var
	 * @return  mixed, with the same variable type
	 **/
	public static function urlencode3986_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'urlencode3986_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::urlencode3986($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return str_replace('%7E', '~', rawurlencode($var));
		} else {
			return $var;
		}
	}

	/**
	 * Urldecode a variable recursively according to the RFC3986, array keys
	 * and object property names will not be decoded, so you would better use
	 * ASCII to define the array key name or object property name.
	 *
	 * @param mixed $var
	 * @return  mixed, with the same variable type
	 **/
	public static function urldecode3986_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'urldecode3986_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::urldecode3986_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return rawurldecode($var);
		} else {
			return $var;
		}
	}

	/**
	 * Base64_encode a variable recursively, array keys and object property
	 * names will not be encoded, so you would better use ASCII to define the
	 * array key name or object property name.
	 *
	 * @param mixed $var
	 * @return mixed, with the same variable type
	 **/
	public static function base64_encode_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'base64_encode_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::base64_encode_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return base64_encode($var);
		} else {
			return $var;
		}
	}

	/**
	 * Base64_decode a variable recursively, array keys and object property
	 * names will not be decoded, so you would better use ASCII to define the
	 * array key name or object property name.
	 *
	 * @param mixed $var
	 * @return mixed, with the same variable type
	 **/
	public static function base64_decode_recursive($var)
	{
		if (is_array($var)) {
			return array_map(array('Utils', 'base64_decode_recursive'), $var);
		} elseif (is_object($var)) {
			$rvar = null;
			foreach ($var as $key => $val) {
				$rvar->{$key} = self::base64_decode_recursive($val);
			}
			return $rvar;
		} elseif (is_string($var)) {
			return base64_decode($var);
		} else {
			return $var;
		}
	}

	/**
	 * Remove BOM string (0xEFBBBF in hex) for input string which is added
	 * by windows when create a UTF-8 file.
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function remove_bom($str)
	{
		if (substr($str, 0, 3) === pack('CCC', 0xEF, 0xBB, 0xBF)) {
			$str = substr($str, 3);
		}
		return $str;
	}

	/**
	 * Generate a unique random key using the methodology
	 * recommend in php.net/uniqid
	 *
	 * @return string a unique random hex key
	 **/
	public static function generate_rand_key()
	{
		return md5(uniqid(mt_rand(), true));
	}

	/**
	 * Generate a random string of specifified length
	 * 目前应用创建产生的api key和secret key使用该算法，切记不能改动。
	 * 
	 * @param  int    $len    default 32
	 * @param  string $seed
	 * @return string
	 */
	public static function generate_rand_str($len = 32, $seed = '')
	{
		if (empty($seed)) {
			$seed = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ';
		}
		$seed_len = strlen($seed);
		$word = '';
		//随机种子更唯一
		mt_srand((double)microtime() * 1000000 * getmypid());
		for ($i = 0; $i < $len; ++$i) {
			$word .= $seed{mt_rand() % $seed_len};
		}
		return $word;
	}

	/**
	 * Trim the right '/'s of an uri path, e.g. '/xxx//' will be sanitized to '/xxx'
	 *
	 * @param string $uri URI to be trim
	 * @return string sanitized uri
	 **/
	public static function sanitize_uri_path($uri)
	{
		$arrUri = explode('?', $uri);
		$arrUri = parse_url($arrUri[0]);
		$path = $arrUri['path'];

		$path = rtrim(trim($path), '/');
		if (!$path) {
			return '/';
		}
		return preg_replace('#/+#', '/', $path);
	}

	/**
	 * Check whether input url has http:// or https:// as its scheme,
	 * if hasn't, it will add http:// as its prefix
	 * @param string $url
	 * @return string
	 */
	public static function http_scheme_auto_complete($url)
	{
		$url = trim($url);
		if (stripos($url, 'http://') !== 0 && stripos($url, 'https://') !== 0) {
			$url = 'http://' . $url;
		}
		return $url;
	}
	
	/**
	 * Check whether the url is under allowed domains
	 * 
	 * @param string $url Url to be check
	 * @param array|string $allowed_domains domain list in index array or ',' seperated string
	 * @return bool
	 */
	public static function is_domain_allowed($url, $allowed_domains)
	{
		if (is_string($allowed_domains)) {
			$allowed_domains = explode(',', $allowed_domains);
		}

		$host = parse_url($url, PHP_URL_HOST);
		if (empty($host)) {
			return false;
		}
		
		foreach ($allowed_domains as $domain) {
			if (self::ends_with($host, $domain)) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Check if the two bytes are a chinese charactor
	 *
	 * @param char $lower_chr	lower bytes of the charactor
	 * @param char $higher_chr	higher bytes of the charactor
	 * @return bool Returns true if it's a chinese charactor, or false otherwise
	 **/
	public static function is_cjk($lower_chr, $higher_chr)
	{
		if (($lower_chr >= 0xb0 && $lower_chr <= 0xf7 && $higher_chr >= 0xa1 && $higher_chr <= 0xfe) ||
			($lower_chr >= 0x81 && $lower_chr <= 0xa0 && $higher_chr >= 0x40 && $higher_chr<=0xfe) ||
			($lower_chr >= 0xaa && $lower_chr <= 0xfe && $higher_chr >= 0x40 && $higher_chr <=0xa0)) {
				return true;
			}
		return false;
	}

	/**
	 * 检查一个字符是否是gbk图形字符
	 *
	 * @param char $lower_chr	lower bytes of the charactor
	 * @param char $higher_chr	higher bytes of the charactor
	 * @return bool Returns true if it's a chinese graph charactor, or false otherwise
	 * @author liaohq
	 **/
	public static function is_gbk_graph($lower_chr, $higher_chr)
	{
		if (($lower_chr >= 0xa1 && $lower_chr <= 0xa9 && $higher_chr >= 0xa1 && $higher_chr <= 0xfe) ||
			($lower_chr >= 0xa8 && $lower_chr <= 0xa9 && $higher_chr >= 0x40 && $higher_chr <= 0xa0)) {
				return true;
			}
		return false;
	}

	/**
	 * 检查字符串中每个字符是否是gbk范围内可见字符，包括图形字符和汉字, 半个汉字将导致检查失败,
	 * ascii范围内不可见字符允许，默认$str是gbk字符串,如果是其他编码可能会失败
	 * 
	 * @param string $str string to be checked
	 * @return  bool 都是gbk可见字符则返回true，否则返回false
	 **/
	public static function  check_gbk_seen($str)
	{
		$len = strlen($str);
		$chr_value = 0;

		for ($i = 0; $i < $len; $i++) {
			$chr_value = ord($str[$i]);
			if ($chr_value < 0x80) {
				continue;
			} elseif ($chr_value === 0x80) {
				//欧元字符;
				return false;
			} else {
				if ($i + 1 >= $len) {
					//半个汉字;
					return false;
				}
				if (!self::is_cjk(ord($str[$i]), ord($str[$i + 1])) &&
					!self::is_gbk_graph(ord($str[$i]), ord($str[$i + 1]))) {
						return false;
					}
			}
			$i++;
		}
		return true;
	}

	/**
	 * 检查$str是否由汉字/字母/数字/下划线/.组成，默认$str是gbk编码
	 *
	 * @param string $str string to be checked
	 * @return  bool
	 **/
	public static function check_cjkalnum($str)
	{
		$len = strlen($str);
		$chr_value = 0;

		for ($i = 0; $i < $len; $i++) {
			$chr_value = ord($str[$i]);
			if ($chr_value < 0x80) {
				if (!ctype_alnum($str[$i]) && $str[$i] != '_' && $str[$i] != '.') {
					return false;
				}
			} elseif ($chr_value === 0x80) {
				//欧元字符;
				return false;
			} else {
				if ($i + 1 >= $len) {
					//半个汉字;
					return false;
				}
				if (!self::is_cjk(ord($str[$i]), ord($str[$i + 1]))) {
					return false;
				}
				$i++;
			}
		}
		return true;
	}

	/**
	 * 检查字符串是否是gbk汉字，默认字符串的编码格式是gbk
	 *
	 * @param string $str string to be checked
	 * @return  bool
	 **/
	public static function check_cjk($str)
	{
		$len = strlen($str);
		$chr_value = 0;

		for ($i = 0; $i < $len; $i++) {
			$chr_value = ord($str[$i]);
			if ($chr_value <= 0x80) {
				return false;
			} else {
				if ($i + 1 >= $len) {
					//半个汉字;
					return false;
				}
				if (!self::is_cjk(ord($str[$i]), ord($str[$i + 1]))) {
					return false;
				}
				$i++;
			}
		}
		return true;
	}

	/**
	 * check whether the url is safe
	 * 
	 * @param string $url	URL to be checked
	 * @return bool
	 **/
	public static function is_valid_url($url)
	{
		if (strlen($url) > 0) {
			if (!preg_match('/^https?:\/\/[^\s&<>#;,"\'\?]+(|#[^\s<>"\']*|\?[^\s<>"\']*)$/i',
				$url, $match)) {
					return false;
				}
		}
		return true;
	}

	/**
	 * check whether the email address is valid
	 * 
	 * @param string $email Email to be checked
	 * @return bool
	 **/
	public static function is_valid_email($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
		/*
		if (strlen($email) > 0) {
			if (!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/i',
							$email, $match)) {
				return false;
			}
		}
		return true;
		 */
	}
	
	/**
	 * Check whether the email is in the specified whitelist domains
	 * @param string $email Email to be checked
	 * @param array|string $whitelist Domain list seperated by ',' or an index array
	 * @return bool
	 */
	public static function is_email_in_whitelist($email, $whitelist)
	{
		if (!self::is_valid_email($email)) {
			return false;
		}
		
		if (is_string($whitelist)) {
			$whitelist = explode(',', $whitelist);
		}
		
		list($user, $domain) = explode('@', $email);
		if (empty($domain)) {
			return false;
		}
		
		return in_array($domain, $whitelist);
	}

	/**
	 * Check whether it is a valid phone number
	 * 
	 * @param string $phone	Phone number to be checked
	 * @return bool
	 **/
	public static function is_valid_phone($phone)
	{
		if (strlen($phone) > 0) {
			if (!preg_match('/^([0-9]{11}|[0-9]{3,4}-[0-9]{7,8}(-[0-9]{2,5})?)$/i',
				$phone, $match)) {
					throw new Exception("carpool.param invalid phone : $phone");
				}
		}
		return true;
	}

	/**
	 * Check whether it is a valid ip list, each ip is delemited by ','
	 * 
	 * @param string $iplist Ip list string to be checked
	 * @return bool
	 **/
	public static function is_valid_iplist($iplist)
	{
		$iplist = trim($iplist);
		if (strlen($iplist) > 0) {
			if (!preg_match('/^(([0-9]{1,3}\.){3}[0-9]{1,3})(,(\s)*([0-9]{1,3}\.){3}[0-9]{1,3})*$/i',
				$iplist, $match)) {
					return false;
				}
		}
		return true;
	}

	/**
	 * Generate a signature.  Should be copied into the client
	 * library and also used on the server to validate signatures.
	 *
	 * @param array	$params	params to be signatured
	 * @param string $secret	secret key used in signature
	 * @param string $signNameSpace	prefix of the param name, all params whose name are equal
	 * with $signNameSpace will not be put in the signature.
	 * @return string md5 signature
	 **/
	public static function generate_sig($params, $secret, $signNameSpace = 'bd_sig')
	{
		$str = '';
		ksort($params);
		foreach ($params as $k => $v) {
			if ($k != $signNameSpace && !is_null($v)) {
				$str .= "$k=$v";
			}
		}
		$str .= $secret;
		return md5($str);
	}

	/**
	 * Generate a 64 unsigned number signature.
	 *
	 * @param array	$params	params to be signatured
	 * @return int 64 unsigned number signature, string format
	 **/
	public static function sign64($value) {
		$str = md5 ( $value, true );
		$high1 = unpack ( "@0/L", $str );
		$high2 = unpack ( "@4/L", $str );
		$high3 = unpack ( "@8/L", $str );
		$high4 = unpack ( "@12/L", $str );
		if(!isset($high1[1]) || !isset($high2[1]) || !isset($high3[1]) || !isset($high4[1]) ) {
			return false;
		}
		$sign1 = $high1 [1] + $high3 [1];
		$sign2 = $high2 [1] + $high4 [1];
		$sign = ($sign1 & 0xFFFFFFFF) | ($sign2 << 32);
		return sprintf ( "%u", $sign );
	}
	
	public static function sign63($value) {
		$str = md5 ( $value, true );
		$high1 = unpack ( "@0/L", $str );
		$high2 = unpack ( "@4/L", $str );
		$high3 = unpack ( "@8/L", $str );
		$high4 = unpack ( "@12/L", $str );
		if(!isset($high1[1]) || !isset($high2[1]) || !isset($high3[1]) || !isset($high4[1]) ) {
			return false;
		}
		$sign1 = $high1 [1] + $high3 [1];
		$sign2 = $high2 [1] + $high4 [1];
		$sign = ($sign1 & 0xFFFFFFFF) | (($sign2 & 0x7FFFFFFF) << 32);
		return $sign;
	}

	/**
	 * Generate a number mod result.
	 *
	 * @param int	$number	params to be mod
	 * @param int	$mod	params to mod
	 * @return int mod result of the number
	 **/
	public static function mod($number, $mod) {
		if(0 < intval($number)) {
			return $number%$mod;
		}
		$length = strlen($number);
		$left = 0;
		for($i = 0; $i < $length; $i++) {
			$digit = substr($number, $i, 1);
			$left = intval($left.$digit);
			if($left < $mod) {
				continue;
			}else if($left == $mod) {
				$left = 0;
				continue;
			}else{
				$left = $left%$mod;
			}
		}
		return $left;
	}

	/**
	 * Check the array contains key or not.
	 *
	 * @param array	$arr_need	keys must exist
	 * @param array $arr_arg	array to check
	 * @return boolean true | false
	 **/
	static function check_exist_array($arr_need, $arr_arg) {
		$arr_diff = array_diff ( $arr_need, array_keys ( $arr_arg ) );
		if (! empty ( $arr_diff )) {
			return false;
		}
		return true;
	}

	/**
	 * Check the int input is valid or not.
	 *
	 * @param int $value	number value
	 * @param int $max max value to check
	 * @param int $min min value to check
	 * @param boolean $compare true to check max,false not to check max
	 * @return boolean true | false
	 **/
	static function check_int($value, $min = 0, $max = -1, $compare = true) {
		if(is_null($value)) {
			throw new Exception("carpool.param invalid num, $value");
		}
		if(!is_numeric($value)) {
			throw new Exception("carpool.param invalid num, $value");
		}
		// 注意：intval('0123') = 123, 将会通过此检查 
		if(intval($value) != $value) {
			throw new Exception("carpool.param invalid num, $value");
		}
		if(true === $compare && $value < $min) {
			throw new Exception("carpool.param invalid num, $value");
		}
		if(true === $compare && 0 <= $max && $max < $value) {
			throw new Exception("carpool.param invalid num, $value");
		}		
		return true;
	}

	/**
	 * Check the string input length is valid or not.
	 *
	 * @param int $value	string value
	 * @param int $max_length max value length to check
	 * @param int $min_length min value length to check
	 * @return boolean true | false
	 **/
	static function check_string($value, $min_length = 1, $max_length = NULL) {
		if(is_null($value) || is_array($value)) {
			throw new Exception("carpool.param invalid length, $value");
		}
		if(strlen($value) < $min_length) {
			throw new Exception("carpool.param invalid length, $value");
		}
		if(!is_null($max_length) && strlen($value) > $max_length) {
			throw new Exception("carpool.param invalid length, $value");
		}		
		return true;
	}
	/**
	 * Check the value input is null.
	 *
	 * @param string $key	for log
	 * @param string $value for check 	 
	 * @return boolean true | exception
	**/
	static function check_null($key , $value) {
		if (is_null($value)){
			throw new Exception("carpool.param key is null : $key");
		} 	
		return true;	
	}
	/**
	 * Check the value input is array.
	 *
	 * @param string $key	for log
	 * @param string $value for check 	 
	 * @return boolean true | exception
	**/
	static function check_array($key , $value) {
		if (!is_array($value)){
			throw new Exception("carpool.param not array : $key");
		} 	
		return true;	
	}		

	/**
	 * Check whether an array is a simple array without key => value pairs 
	 */
	static function check_vector(array $vector)
	{
	    $next = 0;
	    foreach ($vector as $k => $v) {
	        if ($k !== $next) {
	            return false;
	        }
	        $next++;
	    }
	    return true;
	}
	
	public static function encodeUserId($user_id)
	{
        $id = ($user_id & 0x0000ff00) << 16;
        $id += (($user_id & 0xff000000) >> 8) & 0x00ff0000;
        $id += ($user_id & 0x000000ff) << 8;
        $id += ($user_id & 0x00ff0000) >> 16;
        $id ^= 282335;
        return $id; 
    }
    
    public static function decodeUserId($user_id)
    {
	    if (!is_int($user_id) && !is_numeric($user_id)) {
            return false;
        }
        $user_id ^= 282335;
        $id = ($user_id & 0x00ff0000) << 8;
        $id += ($user_id & 0x000000ff) << 16;
        $id += (($user_id & 0xff000000) >> 16) & 0x0000ff00;
        $id += ($user_id & 0x0000ff00) >> 8;
        return $id;
    }
    
    public static function myBaseName($path)
    {
    	return mb_substr($path, mb_strrpos($path, '/')+1);
    }
    
    public static function getUserPhoneUrl($uid, $uname = '')
    {
    	$photoname = Ucrypt::ucrypt_encode($uid,$uname);
        $photoname = $photoname.'.jpg';
        $url = "http://himg.bdimg.com/sys/portrait/item/";
        $url .= $photoname;
        return $url;
    }
    
    // Return true if one of the specified mobile browsers is detected
    public static function isMobileAgent()
    {
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } else {
            return preg_match("/mobile/i", $_SERVER['HTTP_USER_AGENT']);
        }
    }
        
    // 把电话号码中的45%换成“*”
    // 如'12345678901' => '123****8901'
    public static function maskMobile($text, $mask = '*', $percentage = 45)
    {
        $width = intval(strlen($text) * $percentage / 100);
        $start = intval((strlen($text) - $width) / 2);
        return substr($text, 0, $start) . str_repeat($mask, $width) . substr($text, $start + $width);
    }


    public static  function getRandStr($len)
    {
		return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)), 0, $len);
	}
	
	public static  function checkVcode($vcode, $input, $appkey)
    {
    	if(!extension_loaded('vcode'))
    	{
    		return true;
    	}	
    	$expires = vcode_check($vcode, $appkey);
    	if($expires < 0 || $expires > 60)
    	{
    		return false;
    	}	

		return vcode_verify($vcode, $input, 3600); 
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
