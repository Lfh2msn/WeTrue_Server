<?php namespace App\Models\Get;

use App\Models\{
	ConfigModel,
	DisposeModel
};

class GetAeChainModel {
//获取Model
	public function __construct(){
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
    }

	public function microBlockTime($microBlockHash)
	{//微块时间
        $bsConfig = $this->ConfigModel-> backendConfig();
        $url	  = $bsConfig['backendServiceNode'].'/v3/micro-blocks/hash/'.$microBlockHash.'/header';
        @$get	  = file_get_contents($url);
		$num = 0;
		while ( !$get && $num < 20 ) {
			@$get = file_get_contents($url);
			$num++;
			$logMsg = "读取micro_blocks失败:{$url}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			sleep(6);
		}

		$json = (array) json_decode($get, true);
		$utcTime = $json['time'];

		if (empty($utcTime)) {
			$logMsg = "读取微块时间失败:{$url}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
        	return "Get MicroBlock Time Error";
        }

		return $utcTime;
	}

	public function transactions($hash)
	{//获取tx 详情
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url  = $bsConfig['backendServiceNode'].'/v3/transactions/'.$hash;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$mh   = substr($json['block_hash'], 0, 3);
		$num  = 0;
		while ( !$get && $num < 20 || $mh != "mh_") {
			@$get	   = file_get_contents($url);
			$json	   = (array) json_decode($get, true);
			$mh = substr($json['block_hash'], 0, 3);
			$num++;
			sleep(6);
		}

        if (!$get || $mh != "mh_") {
			$logMsg = "节点读取错误:{$hash}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
        	return "Node Data Error";
        }

        $json = (array) json_decode($get, true);
		return $json;
	}

	public function accountsBalance($address)
	{//获取账户AE金额
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url  = $bsConfig['backendServiceNode'].'/v3/accounts/'.$address;
		@$get = file_get_contents($url);
		$num  = 0;
		while (!$get && $num < 20) {
			@$get = file_get_contents($url);
			$num++;
			$logMsg = "读取accounts失败:{$url}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			sleep(6);
		}

		$json    = (array) json_decode($get, true);
		$balance = $json['balance'];

		if (empty($balance)) {
			$logMsg = "读取账户AE金额失败:{$url}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
        	return "Get Accounts Balance Error";
        }

		return $balance;
	}

	public function txSenderId($hash)
	{//获取tx 发送人ID
        $json   = $this->transactions($hash);
		$caller = $json['tx']['sender_id'] ?? $json['tx']['caller_id'];
		if (!$caller) {
			$logMsg = "查不到发送人:{$hash}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
        	return "empty";
        }
		return $caller;
	}

	public function chainHeight($hash="null")
	{//获取链上高度
		$bsConfig  = $this->ConfigModel-> backendConfig();
        $url  = $bsConfig['backendServiceNode'].'/v3/key-blocks/current/height';
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$num  = 0;
		while ( !$json && $num < 20) {
			@$get   = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$num++;
			sleep(6);
		}

		if (empty($json)) {
			$logMsg = "获取链上高度失败--HASH:{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return (int)$json['height'];
	}

	public function addressByNamePoint($names)
	{//AENS获取AE指向地址
		$bsConfig  = $this->ConfigModel-> backendConfig();
        $url   = $bsConfig['backendServiceNode'].'/v3/names/'.$names;
		@$get  = file_get_contents($url);
		$json  = (array) json_decode($get, true);
		$owner = $json['owner'];
		$num   = 0;
		while (!$owner && $num < 5) {
			@$get  = file_get_contents($url);
			$json  = (array) json_decode($get, true);
			$owner = $json['owner'];
			$num++;
			sleep(6);
		}
		if (empty($owner)) {
			$logMsg = "获取链上AENS失败--AENS:{$names}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		$pointers = $json['pointers'];
		$address  = "";
		foreach ($pointers as $row) {
			if ($row['key'] == "account_pubkey") {
				$address = $row['id'];
			}
		}
		if (!$address) { //没有指向则指向持有人
			$address = $owner;
		}
		return $address;
	}

}

