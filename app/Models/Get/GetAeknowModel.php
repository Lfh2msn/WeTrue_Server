<?php 
namespace App\Models\Get;

use App\Models\DisposeModel;

class GetAeknowModel
{//获取Model

	public static function tokenTx($hash)
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
			$logMsg = "API tokenTx json 读取失败:{$hash}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		return $json;
	}

	public static function tokenPayloadTx($hash)
	{//获取Aeknow API AEX9合约Token 带Payload信息
        $url  = 'https://www.aeknow.org/api/tokentx/'.$hash;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$r_id = $json['recipient_id'];
		$num  = 0;
		while (!$r_id && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$r_id = $json['recipient_id'];
			$num++;
			sleep(6);
		}

		if (empty($r_id)) {
			$logMsg = "API tokenPayloadTx json 读取失败:{$hash}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		
		return $json;
	}

	public static function latestSpendTx($address)
	{//获取最新十条tx发送人
	/*
	//从 mdw 获取
		$url = 'https://mainnet.aeternity.io/mdw/txs/backward?account='.$recipient.'&limit=10&page=1';
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);		
		$send = $json['data']['tx']['sender_id'];
		$num  = 0;
		
		while (!$send && $num < 2) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$send = $json['data']['tx']['sender_id'];
			$num++;
			sleep(2);
		}

        if (empty($send)) {
			$logMsg = "latestSpendTx 最新N条发送人读取失败:{$address}";
			$path   = "mdw_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg);
			return;
        }

		$data = $json['data'];
		$sender = [];
		foreach ($data as $row){
			if ($row['recipient_id'] == $address) {
				$sender[] = $row['tx']['sender_id'];
			}
		}
		$sender = array_unique($sender);
		$sender = array_values($sender);
		return $sender;
	*/

	//从 aeknow 获取
		$url = "https://aeknow.org/api/spendtx/".$address;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$send = $json['txs'][0]['sender_id'] ?? $json['txs'][1]['sender_id'];
		$num  = 0;
		while (!$send && $num < 5) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$send = $json['txs'][0]['sender_id'] ?? $json['txs'][1]['sender_id'];
			$num++;
			sleep(5);
		}

        if (empty($send)) {
			$logMsg = "latestSpendTx 最新N条发送人读取失败:{$address}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
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

	public static function tokenList($address)
	{//获取Aeknow API Token 列表
		// token/ak_ID
        $url  = 'https://www.aeknow.org/api/token/'.$address;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$num  = 0;
		while (!$json && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$num++;
			sleep(6);
		}

		if (empty($json)) {
			$logMsg = "spendTx json 读取失败:{$param}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		return $json;
	}

	public static function spendTx($param)
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
			$logMsg = "spendTx json 读取失败:{$param}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		return $json;
	}
	
	public static function tokenTxs($param)
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
			$logMsg = "tokenTxs json 读取失败:{$param}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		return $json;
	}

	public static function myToken($param)
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
			$logMsg = "myToken json 读取失败:{$param}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return;
        }
		return $json;
	}

}

