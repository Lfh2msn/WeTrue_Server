<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class GetModel extends ComModel {
//获取Model
	public function __construct(){
		parent::__construct();
		//$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
    }

	public function getMicroBlockTime($microBlockHash)
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

	public function getTransactions($hash)
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
	
	public function getSenderByLatestTx($address)
	{//获取最新十条tx发送人
		//$bsConfig = $this->ConfigModel-> backendConfig();
		//$url = $bsConfig['backendServiceMdw'].'/txs/backward?account='.$recipient.'&limit=10&page=1';
		$url = "https://aeknow.org/api/spendtx/".$address;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$send = $json['txs'][0]['sender_id'];
		$num  = 0;
		while (!$send && $num < 5) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$send = $json['txs'][0]['sender_id'];
			$num++;
			sleep(5);
		}

        if (empty($send)) {
			$logMsg = "获取最新N条发送人失败--:{$address}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }

		$data = $json['txs'];
		$sender = [];
		foreach ($data as $row){
			if ($row['recipient_id'] == $address) {
				$sender[] = $row['sender_id'];
			}
		}
		$sender = array_unique($sender);
		$sender = array_values($sender);
		return $sender;
	}
	

	public function getAccountsBalance($address)
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

	public function getTxSenderId($hash)
	{//获取tx 发送人ID
        $json = $this->getTransactions($hash);
		if (empty($json)) {
			$logMsg = "查不到发送人:{$hash}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
        	return "empty";
        }
		return $json['tx']['sender_id'];
	}

	public function getChainHeight($hash="null")
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

	public function getAddressByNamePoint($names)
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

	public function getAeknowContractTx($hash)
	{//获取Aeknow API AEX9合约Token信息
        $url  = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$s_id = $json['sender_id'];
		$num  = 0;
		while (!$s_id && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$s_id = $json['sender_id'];
			$num++;
			sleep(6);
		}

		if (empty($s_id)) {
			$logMsg = "获取AEKnow-API-Contracttx-错误:{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	public function getAeknowTokenList($address)
	{//获取Aeknow API Token 列表
		// token/ak_ID
        $url  = 'https://www.aeknow.org/api/token/'.$address;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$s_id = $json['tokens'];
		$num  = 0;
		while (!$s_id && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$s_id = $json['tokens'];
			$num++;
			sleep(6);
		}

		if (empty($s_id)) {
			$logMsg = "获取AEKnow-API-Token-List错误:{$address}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	

	public function getAeknowSpendtx($param)
	{//获取Aeknow API Token 交易记录
		// spendtx/ak_ID/20/0
        $url  = 'https://www.aeknow.org/api/spendtx/'.$param[0].'/'.$param[1].'/'.$param[2];
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
			$logMsg = "获取AEKnow-API-Token-txs错误:{$param}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	public function getAeknowTokentxs($param)
	{//获取Aeknow API Token 交易记录
		// tokentxs/ak_ID/ct_ID/20/0
        $url  = 'https://www.aeknow.org/api/tokentxs/'.$param[0].'/'.$param[1].'/'.$param[2].'/'.$param[3];
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
			$logMsg = "获取AEKnow-API-Token-txs错误:{$param}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	public function getAeknowMyToken($param)
	{//获取Aeknow API Token 交易记录
		// mytoken/ak_ID/ct_ID
        $url  = 'https://www.aeknow.org/api/mytoken/'.$param[0].'/'.$param[1];
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
			$logMsg = "获取AEKnow-API-MyToken错误:{$param}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

}

