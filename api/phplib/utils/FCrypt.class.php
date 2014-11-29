<?php

/**
 * mcrypt wrapper
 */
class FCrypt
{
	private $key;
	private $modes = MCRYPT_MODE_ECB;
	private $cipher = MCRYPT_DES;

	public function __construct($key)
	{
		$this->key = $key;
	}

	public function encrypt($input)
	{
		$size = mcrypt_get_block_size($this->cipher, $this->modes);
		$input = $this->pkcs5_pad($input, $size);
		$td = mcrypt_module_open($this->cipher, '', $this->modes, '');
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		@mcrypt_generic_init($td, $this->key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $data;
	}

	public function decrypt($encrypted)
	{
		$td = mcrypt_module_open($this->cipher, '', $this->modes, '');
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		@mcrypt_generic_init($td, $this->key, $iv);
		$decrypted = mdecrypt_generic($td, $encrypted);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$y = $this->pkcs5_unpad($decrypted);
		return $y;
	}

	private function pkcs5_pad($text, $blocksize)
	{
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	private function pkcs5_unpad($text)
	{
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100 */
