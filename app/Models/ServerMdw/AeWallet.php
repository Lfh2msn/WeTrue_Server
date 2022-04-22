<?php namespace App\Models\ServerMdw;

use App\Models\ComModel;
use App\Models\ConfigModel;

class AeWallet extends ComModel {
//WeTrue MDW 钱包交互
	public function __construct(){
		parent::__construct();
		//$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
    }

	public function newCreateWallet()
	{//通过中间件，创建一个新钱包
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url  = $bsConfig['wetrueMdw'].'/aesdk/v1/createWallet';
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$sk   = $json['secretKey'];
		$num  = 0;
		while ( !$sk && $num < 10) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$sk   = $json['secretKey'];
			$num++;
			sleep(6);
		}
		return $json;
	}
}

