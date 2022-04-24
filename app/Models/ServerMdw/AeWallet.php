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
	{ //通过中间件，创建一个新钱包
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

	public function spendAE($data)
	{ //发送AE转账
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url = $bsConfig['wetrueMdw'].'/aesdk/v1/spendAE';
		$data['node'] = $bsConfig['backendServiceNode'];
		$res = $this->curlPost($data, $url);
		return $res;
	}

	public function transferToken($data)
	{ //发送AEX-9 Token转账
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url = $bsConfig['wetrueMdw'].'/aesdk/v1/transferToken';
		$data['node'] = $bsConfig['backendServiceNode'];
		$res = $this->curlPost($data, $url);
		return $res;
	}

	private function curlPost($data, $url)
	{ //Post
		try {
			$data_json = json_encode($data);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			return $response;
		} catch (Exception $e) {
			return false;
		}
	}

}

