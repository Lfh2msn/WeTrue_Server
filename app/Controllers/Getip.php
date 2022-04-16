<?php
namespace App\Controllers;

class Getip extends BaseController {

	public function index()
	{//获取本地IP
		if ( isset($_SERVER) ) {    
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			} else {
				$realip = $_SERVER['REMOTE_ADDR'];
			}
		} else {
			//不允许就使用getenv获取  
			if ( getenv("HTTP_X_FORWARDED_FOR") ) {
				  $realip = getenv( "HTTP_X_FORWARDED_FOR");
			} elseif ( getenv("HTTP_CLIENT_IP") ) {
				  $realip = getenv("HTTP_CLIENT_IP");
			} else {
				  $realip = getenv("REMOTE_ADDR");
			}
		}

		echo $realip;
    }

	public function getIP() { /*获取客户端IP*/
		if (@$_SERVER["HTTP_X_FORWARDED_FOR"])
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		else if (@$_SERVER["HTTP_CLIENT_IP"])
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		else if (@$_SERVER["REMOTE_ADDR"])
			$ip = $_SERVER["REMOTE_ADDR"];
		else if (@getenv("HTTP_X_FORWARDED_FOR"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (@getenv("HTTP_CLIENT_IP"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if (@getenv("REMOTE_ADDR"))
			$ip = getenv("REMOTE_ADDR");
		else
			$ip = "Unknown";
		echo $ip;
	}


}