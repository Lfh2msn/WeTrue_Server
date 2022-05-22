<?php namespace App\Models\Get;

use App\Models\DisposeModel;

class GetAeknowModel {
//获取Model
	public function __construct(){
		$this->DisposeModel = new DisposeModel();
    }

	public function tokenTx($hash)
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
			$logMsg = "获取AEKnow-API-tokenTx-错误:{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	public function tokenPayloadTx($hash)
	{//获取Aeknow API AEX9合约Token 带Payload信息
        $url  = 'https://www.aeknow.org/api/tokentx/'.$hash;
		@$get = file_get_contents($url);
		//$json = (array) json_decode($get, true);

		//$s_id = $json['sender_id'];

		/*
		$num  = 0;
		while (!$s_id && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$s_id = $json['sender_id'];
			$num++;
			sleep(6);
		}

		if (empty($s_id)) {
			$logMsg = "获取AEKnow-API-tokenPayloadTx-错误:{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		
		return $s_id;*/
	}

	public function latestSpendTx($address)
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

	public function tokenList($address)
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

	public function spendTx($param)
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
	
	public function tokenTxs($param)
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

	public function myToken($param)
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

