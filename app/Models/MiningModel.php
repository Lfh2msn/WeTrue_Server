<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\AecliModel;

class MiningModel extends ComModel
{//挖矿Model

	public function __construct()
	{
		parent::__construct();
		$this->wet_users  = 'wet_users';
		$this->AecliModel = new AecliModel();
		$this->UserModel  = new UserModel();
    }

	public function isMapAccount($address)
	{//验证是否开通映射挖矿
		$sql   = "SELECT is_map FROM $this->wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->is_map ? true : false;
	}

	public function openAccount($hash)
	{//新开户
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$getUrl	   = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$contents = file_get_contents($getUrl);

		if (empty($contents)) {
			log_message('读取aeknow_api失败:'.$hash, 4);
        	return "Get 读取aeknow_api失败 Error";
        }

		$json = (array) json_decode($contents, true);

		$sender_id 	  = $json['sender_id'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = (int)$json['amount'];
		$return_type  = $json['return_type'];
		$block_height = (int)$json['block_height'];
		$contract_id  = $json['contract_id'];
		if (
			$recipient_id == $bsConfig['airdropAddress'] &&
			$amount		  == $bsConfig['mapAccountAmount'] &&
			$contract_id  == $bsConfig['WTTContractAddress'] &&
			$return_type  == "ok"
		) {
			$this->UserModel-> userPut($sender_id);
			$updateSql = "UPDATE $this->wet_users SET is_map = 1 WHERE address = '$sender_id'";
			$this->db->query($updateSql);

			$textFile   = fopen("mining/".date("Y-m-d").".txt", "a");
			$appendText = $sender_id.":".(int)($amount / 1000000000000000000).":".$block_height.":".$hash."\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			$data['code'] = 200;
			$data['msg']  = 'success';
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_unknown';
		}
		return json_encode($data);
	}

	public function mapping($aeAmount)
	{//用户映射挖矿
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$aettos    = (int)($aeAmount * 1000000000000000000);
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		$isMapAccount = $this-> isMapAccount($isAkToken);
		if (!$isAkToken && !$isMapAccount) {
			$data['code'] = 406;
			$data['msg']  = 'did_not_open_mapping';
			return json_encode($data);
		}

		$accountsUrl  = $bsConfig['backendServiceNode'].'v2/accounts/'.$akToken;
		@$getBalance  = file_get_contents($accountsUrl);
		$accountsJson = (array) json_decode($getBalance, true);

		if (empty($getBalance) && $accountsJson['balance'] <= $aettos) {
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
        	return json_encode($data);
        }

		$blocksUrl    = $bsConfig['backendServiceNode'].'v2/blocks/top';
		@$getTop	  = file_get_contents($blocksUrl);
		$blocksJson   = (array) json_decode($getTop, true);
		$blockHeight  = $blocksJson['micro_block']['height'];
		if (empty($blockHeight)) {
			$data['code'] = 406;
			$data['msg']  = 'get_block_height_error';
        	return json_encode($data);
        }

		
	}

}

