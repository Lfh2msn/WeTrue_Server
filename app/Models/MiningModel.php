<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\AecliModel;

class MiningModel extends ComModel
{//挖矿Model

	public function __construct()
	{
		parent::__construct();
		$this->wet_users    = 'wet_users';
		$this->wet_mapping  = 'wet_mapping';
		$this->AecliModel   = new AecliModel();
		$this->UserModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
    }

	public function isMapAccount($address)
	{//验证是否开通映射挖矿
		$sql   = "SELECT is_map FROM $this->wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->is_map ? true : false;
	}

	public function isMapState($address)
	{//验证是否已经映射
		$sql   = "SELECT state FROM $this->wet_mapping WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row->state ? true : false;
	}

	public function openAccount($hash)
	{//新开户
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$getUrl	   = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$contents = file_get_contents($getUrl);
		if (empty($contents)) {
			log_message('读取aeknow_api失败:'.$hash, 4);
        	return "读取aeknow_api失败 Error";
        }
		$json = (array) json_decode($contents, true);

		$blocksUrl    = $bsConfig['backendServiceNode'].'v2/blocks/top';
		@$getTop	  = file_get_contents($blocksUrl);
		$chainJson    = (array) json_decode($getTop, true);
		$chainHeight  = (int)$chainJson['key_block']['height'];

		$sender_id 	  = $json['sender_id'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = (int)$json['amount'];
		$return_type  = $json['return_type'];
		$block_height = (int)$json['block_height'];
		$contract_id  = $json['contract_id'];
		$poorHeight	  = ($chainHeight - $block_height);
		if (
			$recipient_id == $bsConfig['airdropAddress'] &&
			$amount		  == $bsConfig['mapAccountAmount'] &&
			$contract_id  == $bsConfig['WTTContractAddress'] &&
			$poorHeight   <= 500 &&
			$return_type  == "ok"
		) {
			$this->UserModel-> userPut($sender_id);
			$this->db->table($this->wet_users)->where('address', $sender_id)->update( ['is_map' => 1] );
			$textFile   = fopen("mining/".date("Y-m-d").".txt", "a");
			$appendText = "{$sender_id}:{$amount}:{$block_height}:{$hash}\r\n";
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
		$bsConfig   = (new ConfigModel())-> backendConfig();
		$mappingWTT = $bsConfig['mappingWTT'];
		if (!$mappingWTT) {
			$data['code'] = 406;
			$data['msg']  = 'close_mapping';
			return json_encode($data);
		}

		$akToken      = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken    = $this->DisposeModel-> checkAddress($akToken);
		$isMapAccount = $this-> isMapAccount($akToken);
		if (!$isAkToken && !$isMapAccount) {
			$data['code'] = 406;
			$data['msg']  = 'did_not_open_mapping';
			return json_encode($data);
		}

		$isMapping = $this-> isMapState($akToken);
		if ($isMapping) {
			$data['code'] = 406;
			$data['msg']  = 'repeat_mapping';
			return json_encode($data);
		}

		$aettos    	  = (int)($aeAmount * 1e18);
		$accountsUrl  = $bsConfig['backendServiceNode'].'v2/accounts/'.$akToken;
		@$getJson     = file_get_contents($accountsUrl);
		$accountsJson = (array) json_decode($getJson, true);

		if (empty($getJson) && $accountsJson['balance'] <= $aettos) {
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
        	return json_encode($data);
        }

		$blocksUrl   = $bsConfig['backendServiceNode'].'v2/blocks/top';
		@$getBlocks	 = file_get_contents($blocksUrl);
		$blocksJson  = (array) json_decode($getBlocks, true);
		$blockHeight = $blocksJson['key_block']['height'];
		if (empty($blockHeight)) {
			$data['code'] = 406;
			$data['msg']  = 'get_block_height_error';
        	return json_encode($data);
        }

		$insertData = [
			'address'	 => $akToken,
			'height_map' => (int) ($blockHeight),
			'amount'     => (int) $aettos,
			'state'		 => 1,
			'utctime'    => (int) (time() * 1000)
		];
		$this->db->table($this->wet_mapping)->insert($insertData);

		$data['code'] = 200;
		$data['msg']  = 'success';
		return json_encode($data);
	}

	public function checkMapping($address)
	{//映射信息查询
		$isMapping = $this-> isMapState($address);
		if (!$isMapping) {
			$data = 'address_not_mapping';
			return $data;
		}

		$bsConfig     = (new ConfigModel())-> backendConfig();
		$blocksUrl    = $bsConfig['backendServiceNode'].'v2/blocks/top';
		@$getTop	  = file_get_contents($blocksUrl);
		$blocksJson   = (array) json_decode($getTop, true);
		$blockHeight  = (int)$blocksJson['key_block']['height'];
		$sql   = "SELECT address,
						height_map,
						height_check,
						state,
						amount,
						earning
					FROM $this->wet_mapping WHERE address = '$address' AND state = 1 LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		$heightCheckOld = (int)$row->height_check;
		$heightMap	    = (int)$row->height_map;
		$isMapState 	= $this-> isMapState($address);  //黑屋验证
		if ( 
				$blockHeight &&
				$row &&
				$heightCheckOld <= $blockHeight &&
				$isMapState &&
				$heightMap <> 0
			) { //链上状态正常 及 链上高度大于上次校验高度

			if (!$heightCheckOld || $heightCheckOld == 0) {
				$heightCheckOld	= $heightMap;
			}

			$accountsUrl  = $bsConfig['backendServiceNode'].'v2/accounts/'.$address;
			@$getBalance  = file_get_contents($accountsUrl);
			$accountsJson = (array) json_decode($getBalance, true);
			$chainBalance = $accountsJson['balance'];  //链上金额
			$mapAmount 	  = $row->amount;  //映射金额
			if($chainBalance && $chainBalance < $mapAmount) {  //对比[映射]及[链上]金额
				$textFile   = fopen("mining/black-house.txt", "a");
				$appendText = "账户:{$address}\r\n链上:{$chainBalance}\r\n映射:{$mapAmount}\r\n时间:{$blockHeight}-{time()}\r\n\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
				$inMapData = [
					'earning' => 0,
					'amount'  => 0
				];
				$this->db->table($this->wet_mapping)->where('address', $address)->update($inMapData);
				$this->db->table($this->wet_users)->where('address', $address)->update( ['is_map' => 0] );
				$query = $this->db->query($sql);
				$row   = $query->getRow();
				return $row;
			}

			$pastHeight = (int)($blockHeight - $heightCheckOld);
			$aettos    	= ($mapAmount / 1e18);
			$earningOld = $row->earning ?? 0;
			$earningNew	= ( $earningOld + ($pastHeight * $aettos * 14e12) );

			$insertData = [
				'height_check' => $blockHeight,
				'earning' 	   => $earningNew
			];
			$this->db->table($this->wet_mapping)->where('address', $address)->update($insertData);
			$query = $this->db->query($sql);
			$row   = $query->getRow();
        }
		return $row;
	}

	public function unMapping($address)
	{//用户解除映射
		$bsConfig     = (new ConfigModel())-> backendConfig();
		$mappingWTT = $bsConfig['mappingWTT'];
		if (!$mappingWTT) {
			$data['code'] = 406;
			$data['msg']  = 'close_mapping';
			return json_encode($data);
		}

		$insertData = [
			'address'	 => $akToken,
			'height_map' => (int) ($blockHeight),
			'amount'     => (int) $aettos,
			'utctime'    => (int) (time() * 1000)
		];
		$this->db->table($this->wet_mapping)->insert($insertData);

		$data['code'] = 200;
		$data['msg']  = 'success';
		return json_encode($data);
	}

}