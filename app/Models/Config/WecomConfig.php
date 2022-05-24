<?php namespace App\Models\Config;

class WecomConfig {
//企业微信配置

	public function config()
	{//配置
		return array(
			'WECOM_CID_1'    => 'ww17a07df84b0cded3',  //企业微信公司ID
			'WECOM_SECRET_1' => 'AmA3A53QYZ3hJ2tLxjI5sTXGrOitjQCk71FdRlCDvbI',  //企业微信应用Secret
			'WECOM_AID_1'    => '1000002',  //企业微信应用ID
			'WETRUE_KEY_1'   => 'wetrue',  //发送Key
			'WECOM_TOKEN_1'  => 'l6Az3',  //WeTrue Push Token
			'WECOM_AESKEY_1' => 'ldCAbQ3FuydLb5TPBeminnXU88diw5cSdfzKGmQPxIX'  //WeTrue Push AesKey
		);
	}

}