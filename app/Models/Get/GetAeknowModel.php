<?php 
namespace App\Models\Get;

use App\Models\{
	ComModel,
	DisposeModel
};
use App\Models\Config\AeknowConfig;

class GetAeknowModel
{//获取Model

	public static function tokenTx($hash)
	{//获取Aeknow API AEX9合约Token信息
        $url  = AeknowConfig::urls()[0]['url'].'/api/contracttx/'.$hash;
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
        $url  = AeknowConfig::urls()[0]['url'].'/api/tokentx/'.$hash;
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
		$sList = [];
		foreach ($data as $row){
			if ($row['recipient_id'] == $address) {
				$sender[] = $row['tx']['sender_id'];
			}
		}
		$sList = array_unique($sList);
		$sList = array_values($sList);
		return $sList;
	*/

	//从 aeknow 获取
		$url = AeknowConfig::urls()[0]['url'].'/api/spendtx/'.$address;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$num  = 0;
		$sList = [];

		while (!$json && $num < 5) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$num++;
			sleep(5);
		}

        if (!$json['txs']) {
			$logMsg = "latestSpendTx 最新N条发送人读取失败:{$address}";
			$path   = "aeknow_read/".date('Y-m-d');
			DisposeModel::wetFwriteLog($logMsg, $path);
			return $sList;
        }

		$data = $json['txs'];
		
		foreach ($data as $row){
			if ($row['recipient_id'] == $address) {
				$sList[] = $row['sender_id'];
			}
		}
		$sList = array_unique($sList);
		$sList = array_values($sList);
		return $sList;
	}

	public static function wetHdTx($amount)
	{//获取最新100条tx并写入数据库

		$url    = AeknowConfig::urls()[0]['url'].'/api/spendtx/ak_dMyzpooJ4oGnBVX35SCvHspJrq55HAAupCwPQTDZmRDT5SSSW/'.$amount.'/0';
		@$get   = file_get_contents($url);
		$json   = (array) json_decode($get, true);
		$num    = 0;
		$txList = [];

		while (!$json && $num < 5) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$num++;
			sleep(5);
		}

		$data = $json['txs'];

		if($data) {
			foreach ($data as $row){
				if ($row['txtype'] == 'SpendTx') {
					$txList[] = $row['txhash'];
				}
			}

			$toPgArr = DisposeModel::to_pg_val_array($txList); //转换为pgsql所需数组

			$insertTempSql = "INSERT INTO wet_temp(tp_hash) VALUES $toPgArr";
			ComModel::db()-> query($insertTempSql);
			return 'ok,写入'.$amount.'条';
		} else {
			return '读取aek api数组失败';
		}

		
	}

	public static function tokenList($address)
	{//获取Aeknow API Token 列表
		// token/ak_ID
        $url  = AeknowConfig::urls()[0]['url'].'/api/token/'.$address;
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
        $url  = AeknowConfig::urls()[0]['url'].'/api/spendtx/'.$param[0].'/'.$param[1].'/'.$param[2];
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
        $url  = AeknowConfig::urls()[0]['url'].'/api/tokentxs/'.$param[0].'/'.$param[1].'/'.$param[2].'/'.$param[3];
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
        $url  = AeknowConfig::urls()[0]['url'].'/api/mytoken/'.$param[0].'/'.$param[1];
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

