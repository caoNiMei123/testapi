<?php
class Utf8Encode
{
    public static function is_utf8($str)
    {
        return 1 == preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]'.  // ASCII
                    '| [\xC2-\xDF][\x80-\xBF]'.             //non-overlong 2-byte
                    '| \xE0[\xA0-\xBF][\x80-\xBF]'.         //excluding overlongs
                    '| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.  //straight 3-byte
                    '| \xED[\x80-\x9F][\x80-\xBF]'.         //excluding surrogates
                    '| \xF0[\x90-\xBF][\x80-\xBF]{2}'.      //planes 1-3
                    '| [\xF1-\xF3][\x80-\xBF]{3}'.          //planes 4-15
                    '| \xF4[\x80-\x8F][\x80-\xBF]{2}'.      //plane 16
                    ')*$%xs', $str);
    }
    
    private static function is_utf8_encode($str)
    { 
        if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$str) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$str) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$str) == true) 
        { 
            return true; 
        } 
        else 
        { 
            return false; 
        }
    }

    public static function FilterPartialUTF8Char($str)
    {
         $str = preg_replace("/[\\xC0-\\xDF](?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);
         $str = preg_replace("/[\\xE0-\\xEF][\\x80-\\xBF]{0,1}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);
         $str = preg_replace("/[\\xF0-\\xF7][\\x80-\\xBF]{0,2}(?=[\\x00-\\x7F\\xC0-\\xDF\\xE0-\\xEF\\xF0-\\xF7]|$)/", "", $str);
         return $str;
    }

    public static function conv_utf8_iso8859_7($s)
    {
        $len = strlen($s);
        $out = "";
        $curr_char = "";
        for($i=0; $i < $len; $i++) 
        {
            $curr_char .= $s[$i];
            if( ( ord($s[$i]) & (128+64) ) == 128) 
            {
                //character end found
                if ( strlen($curr_char) == 2) 
                {
                        // 2-byte character check for it is greek one and convert
                        if(ord($curr_char[0])==205) $out .= chr( ord($curr_char[1])+16 );
                        else if (ord($curr_char[0])==206) $out .= chr( ord($curr_char[1])+48 );
                        else if (ord($curr_char[0])==207) $out .= chr( ord($curr_char[1])+112 );
                        else ; // non greek 2-byte character, discard character
                } 
                else ;// n-byte character, n>2, discard character
                $curr_char = "";
            } 
            else if (ord($s[$i]) < 128)
            {
                // character is one byte (ascii)
                $out .= $curr_char;
                $curr_char = "";
            }
        }
        return $out;
    }
    
    public static function to_utf8_encode($string) 
    {
        //if(!self::is_utf8($string))
        //{
        //$string = iconv('GBK', 'UTF8', $string);
        $string=mb_convert_encoding($string,'UTF-8','GBK');
        //}
        return $string;
    }

    public static function to_gbk_encode($string) 
    {
        //if(self::is_utf8($string)) 
        //{
        //$string = iconv('UTF8', 'GBK', $string);
        $string=mb_convert_encoding($string,'GBK','UTF-8');
        //}
        return $string;
    }
}
