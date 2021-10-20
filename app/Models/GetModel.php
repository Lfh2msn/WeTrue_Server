<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class GetModel extends Model {
//获取Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
    }

	public function getMicroBlockTime($microBlockHash)
	{//微块时间
        $bsConfig = $this->ConfigModel-> backendConfig();
        $url	  = $bsConfig['backendServiceNode'].'v3/micro-blocks/hash/'.$microBlockHash.'/header';
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
		$url  = $bsConfig['backendServiceNode'].'v3/transactions/'.$hash;
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

	public function getAccountsBalance($address)
	{//获取账户AE金额
		$bsConfig = $this->ConfigModel-> backendConfig();
		$url  = $bsConfig['backendServiceNode'].'v3/accounts/'.$address;
		@$get = file_get_contents($url);
		$num  = 0;
		while ( !$get && $num < 20 ) {
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

	public function getAeknowContractTx($hash)
	{//获取Aeknow API AEX9合约Token信息
        $url  = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$s_id = $json['sender_id'];
		$num  = 0;
		while ( !$s_id && $num < 20) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$s_id = $json['sender_id'];
			$num++;
			sleep(6);
		}

		if (empty($s_id)) {
			$logMsg = "获取AEKnow-API错误：{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return $json;
	}

	public function getChainHeight($hash="null")
	{//获取链上高度
		$bsConfig  = $this->ConfigModel-> backendConfig();
        $url  = $bsConfig['backendServiceNode'].'v3/key-blocks/current/height';
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
			$logMsg = "获取链上高度失败--hash：{$hash}\r\n\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return;
        }
		return (int)$json['height'];
	}

	

}

