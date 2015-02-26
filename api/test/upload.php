<?php
$user_id = $argv[1];
$type = $argv[2];
$fp = fopen($argv[3], "rb"); 
$content = fread($fp, 1024 * 1024);
$curl = curl_init("http://127.0.0.1:8089/rest/2.0/carpool/file?method=upload&devuid=1&ctype=1&user_name=18601165872&user_type=1&user_id=$user_id&type=$type");
curl_setopt($curl, CURLOPT_MAXREDIRS , 3);
curl_setopt($curl, CURLOPT_POST , true);
curl_setopt($curl, CURLOPT_POSTFIELDS , $content);
$result = curl_exec($curl);
