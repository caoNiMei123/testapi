<?php

class Ucrypt
{
    public static function & reinterpret_cast($intUserID)
    {
        $arrValue = array();
        $arrValue[] = $intUserID & 0x000000ff;
        $arrValue[] = ($intUserID & 0x0000ff00) >> 8;
        $arrValue[] = ($intUserID & 0x00ff0000) >> 16;
        $arrValue[] = ($intUserID >> 24) & 0x000000ff;
        
        return $arrValue;
    }

    public static function ucrypt_encode($intUserID, $strUserName = '')
    {
        $strChars = '0123456789abcdef';
        $arrValue = self::reinterpret_cast($intUserID);

        $strCode = $strChars[$arrValue[0] >> 4] . $strChars[$arrValue[0] & 15];
        $strCode .= $strChars[$arrValue[1] >> 4] . $strChars[$arrValue[1] & 15];

        $intLen = strlen($strUserName);

        for( $i = 0; $i < $intLen; ++$i ) {
            $intValue = ord($strUserName[$i]);
            $strCode .= $strChars[($intValue >> 4)] . $strChars[($intValue & 15)];
        }

        $strCode .= $strChars[$arrValue[2] >> 4] . $strChars[$arrValue[2] & 15];
        $strCode .= $strChars[$arrValue[3] >> 4] . $strChars[$arrValue[3] & 15];

        return $strCode;
    }

    public static function ucrypt_decode($strCode, $bolNeedUserName = false) //����
    {
        $intLen = strlen($strCode);
        
        if( $intLen < 10 ) {
            return false;
        }

        $intUserID = hexdec($strCode[$intLen - 2] . $strCode[$intLen - 1]);
        $intUserID = ($intUserID << 8) + hexdec($strCode[$intLen - 4] . $strCode[$intLen - 3]);
        $intUserID = ($intUserID << 8) + hexdec($strCode[2] . $strCode[3]);
        $intUserID = ($intUserID << 8) + hexdec($strCode[0] . $strCode[1]);

        if( $bolNeedUserName ) {
            $intLast = $intLen - 4;
            $strUserName = '';
            for( $i = 4; $i < $intLast; $i += 2 ) {
                $strUserName .= chr(hexdec($strCode[$i] . $strCode[$i + 1]));
            }
            if( strlen($strUserName) > 32 || !preg_match('/^[^<>"\'\/]+$/', $strUserName) ) {
                return false;
            }
            return array ('user_id'  =>  $intUserID,
                          'user_name'=>  $strUserName
                          );
        } else {
            return $intUserID;
        }
    }    

    public static function rc4 ($pwd, $data){
        $key[] = "";
        $box[] = "";
        $cipher = "";
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return $cipher;
    }
}