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

}