<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\AecliModel;
use App\Models\ValidModel;
use App\Models\ConfigModel;

class MiningModel extends ComModel
{//挖矿Model

	public function __construct()
	{
		parent::__construct();
		$this->AecliModel   = new AecliModel();
		$this->UserModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->wet_users    = "wet_users";
		$this->wet_mapping  = "wet_mapping";
		$this->wet_temp     = "wet_temp";
    }

	public function openAccount($address, $hash)
	{//新开户
		$tp_type   = "mapping";
		$isTempHash = $this->ValidModel-> isTempHash($hash);
		if ($isTempHash) {
			echo $this->DisposeModel-> wetJsonRt(406, 'error_repeat_temp');
		} else {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_sender_id, tp_type) VALUES ('$hash', '$address', '$tp_type')";
			$this->db->query($insertTempSql);
			echo $this->DisposeModel-> wetJsonRt(200, 'success');
		}

		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '2 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$tempSql = "SELECT tp_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC LIMIT 30";
		$tempQy  = $this->db-> query($tempSql);
		$tempRes = $tempQy->getResult();
		foreach ($tempRes as $row) {
			$tp_hash = $row->tp_hash;
			$this->decodeMapping($tp_hash);
		}
	}

	public function decodeMapping($hash)
	{//新开户-解码开通
		$bsConfig  = (new ConfigModel())-> backendConfig();
		$getUrl	   = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$contents = file_get_contents($getUrl);
		$json 	   = (array) json_decode($contents, true);
		$sender_id = $json['sender_id'];
		$cuntnum   = 0;
		while ( !$sender_id && $cuntnum < 20) {
			@$contents = file_get_contents($getUrl);
			$json 	   = (array) json_decode($contents, true);
			$sender_id = $json['sender_id'];
			$cuntnum++;
			sleep(6);
		}

		if (empty($sender_id)) {
			$textFile   = fopen("log/mining/error-".date("Y-m-d").".txt", "a");
			$appendText = "开通映射获取AEKnow-API错误--hash：{$hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return;
        }

		$blocksUrl    = $bsConfig['backendServiceNode'].'v3/key-blocks/current/height';
		@$getTop	  = file_get_contents($blocksUrl);
		$chainJson    = (array) json_decode($getTop, true);
		$cuntnum      = 0;
		while ( !$chainJson && $cuntnum < 20) {
			@$getTop   = file_get_contents($blocksUrl);
			$chainJson = (array) json_decode($getTop, true);
			$cuntnum++;
			sleep(6);
		}

		if (empty($chainJson)) {
			$textFile   = fopen("log/mining/error-".date("Y-m-d").".txt", "a");
			$appendText = "开通映射获取链上高度失败--hash：{$hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return;
        }

		$chainHeight  = (int)$chainJson['height'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = $json['amount'];
		$return_type  = $json['return_type'];
		$block_height = (int)$json['block_height'];
		$contract_id  = $json['contract_id'];
		$poorHeight	  = ($chainHeight - $block_height);

		if ($return_type == "revert") {
			$this->deleteTemp($hash);
			return;
		}

		if (
			$recipient_id == $bsConfig['openMapAddress'] &&
			$amount		  == $bsConfig['mapAccountAmount'] &&
			$contract_id  == $bsConfig['WTTContractAddress'] &&
			$poorHeight   <= 480 &&
			$return_type  == "ok"
		) {
			$this->UserModel-> userPut($sender_id);
			$this->db->table($this->wet_users)->where('address', $sender_id)->update( ['is_map' => '1'] );
			$textFile   = fopen("log/mining/open-mapping-".date("Y-m-d").".txt", "a");
			$textTime   = date("Y-m-d h:i:s");
			$wtt_ttos   = ($amount / 1e18);
			$appendText = "开通映射--账户:{$sender_id}\r\n花费WTT:{$wtt_ttos}\r\n高度:{$block_height}\r\n时间:{$textTime}\r\nHash:{$hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			$this->deleteTemp($hash);
		}
	}

	public function inMapping($address, $amount)
	{//用户映射挖矿
		$bsConfig   = (new ConfigModel())-> backendConfig();
		$mappingWTT = $bsConfig['mappingWTT'];
		if (!$mappingWTT) {
			return $this->DisposeModel-> wetJsonRt(406, 'close_mapping');
		}

		$isAddress    = $this->DisposeModel-> checkAddress($address);
		$isMapAccount = $this->ValidModel-> isMapAccount($address);
		if (!$isAddress && !$isMapAccount) {
			return $this->DisposeModel-> wetJsonRt(406, 'did_not_open_mapping');
		}

		$isMapping = $this->ValidModel-> isMapState($address);
		if ($isMapping) {
			return $this->DisposeModel-> wetJsonRt(406, 'repeat_mapping');
		}

		$accountsUrl  = $bsConfig['backendServiceNode'].'v3/accounts/'.$address;
		@$getJson     = file_get_contents($accountsUrl);
		$accountsJson = (array) json_decode($getJson, true);

		if (empty($getJson) && $accountsJson['balance'] <= $amount) {
        	return $this->DisposeModel-> wetJsonRt(406, 'error_amount');
        }

		$blocksUrl   = $bsConfig['backendServiceNode'].'v3/key-blocks/current/height';
		@$getBlocks	 = file_get_contents($blocksUrl);
		$blocksJson  = (array) json_decode($getBlocks, true);
		$cuntnum     = 0;
		while (!$blocksJson && $cuntnum < 5) { //防止意外获取失败
			@$getBlocks = file_get_contents($blocksUrl);
			$blocksJson = (array) json_decode($getBlocks, true);
			$cuntnum++;
		}
		
		$blockHeight = $blocksJson['height'];
		if (empty($blockHeight)) {
        	return $this->DisposeModel-> wetJsonRt(406, 'get_block_height_error');
        }
		$isMapAddress = $this->ValidModel-> isMapAddress($address);
		if ($isMapAddress) {
			$upDate = [
				'height_map'   => $blockHeight,
				'height_check' => $blockHeight,
				'amount'       => $amount,
				'state'		   => 1,
				'utctime'      => (time() * 1000)
			];
			$this->db->table($this->wet_mapping)->where('address', $address)->update($upDate);
		} else {
			$insertData = [
				'address'	   => $address,
				'height_map'   => $blockHeight,
				'height_check' => $blockHeight,
				'amount'       => $amount,
				'state'		   => 1,
				'utctime'      => (time() * 1000)
			];
			$this->db->table($this->wet_mapping)->insert($insertData);
		}
		//写入日志
		$textFile   = fopen("log/mining/".date("Y-m-d").".txt", "a");
		$textTime   = date("Y-m-d h:i:s");
		$aettos     = ($amount / 1e18);
		$appendText = "开启映射--账户:{$address}\r\n映射AE:{$aettos}\r\n时间:{$blockHeight}--{$textTime}\r\n\r\n";
		fwrite($textFile, $appendText);
		fclose($textFile);

		$data['state'] = true;
		return $this->DisposeModel-> wetJsonRt(200, 'success', $data);
	}

	public function unMapping($address)
	{//用户解除映射
		$checkRow = $this-> checkMapping($address);
		if (!$checkRow) {
			return $this->DisposeModel-> wetJsonRt(406, 'error_unknown');
		}

		if ($checkRow['state'] == 0) {
			$data['state'] = false;
			return $this->DisposeModel-> wetJsonRt(406, 'no_mapping', $data);
		}

		$checEarning = $checkRow['earning'];
		$earnLock    = $checkRow['earning_lock'];
		if ($checEarning >= 1e17 && $earnLock == 0) {
			$this->earningLock($address, 1); //打开锁
			$hash = $this->AecliModel-> spendWTT($address, $checEarning);
			$code = $this->DisposeModel-> checkAddress($hash) ? 200 : 406;
		} else {
			$code = 200;
		}

		$msg = 'error_unknown';
		if ($code == 200) {
			$upData = [
				'height_map'   => 0,
				'height_check' => 0,
				'state' 	   => 0,
				'amount'       => 0,
				'earning'      => 0,
				'earning_lock' => 0, //关闭锁
				'utctime'      => (time() * 1000)
			];
			$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
			$data['earning'] = $checEarning;
			$msg = 'success';
			$textFile   = fopen("log/mining/".date("Y-m-d").".txt", "a");
			$textTime   = date("Y-m-d h:i:s");
			$aettos     = ($checkRow['amount'] / 1e18);
			$wtt_ttos   = $checEarning/1e18;
			$appendText = "解除映射--账户:{$address}\r\n映射AE:{$aettos}\r\n领取WTT:{$wtt_ttos}\r\n时间:{$textTime}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
		}
		return $this->DisposeModel-> wetJsonRt($code, $msg, $data);
	}

	public function getEarning($address)
	{//用户领取收益
		$checkRow = $this-> checkMapping($address);
		if (!$checkRow) {
			return $this->DisposeModel-> wetJsonRt(406,'error_unknown1',[]);
		}

		if ($checkRow['state'] == 0) {
			$data['state'] = false;
			return $this->DisposeModel-> wetJsonRt(406,'no_mapping',$data);
		}

		$checEarning = $checkRow['earning'];
		$earnLock    = $checkRow['earning_lock'];
		if ($checEarning >= 1e17 && $earnLock == 0) {
			$this->earningLock($address, 1); //打开锁
			$hash = $this->AecliModel-> spendWTT($address, $checEarning);
			$code = $this->DisposeModel-> checkAddress($hash) ? 200 : 406;
			if ($code == 200) {
				$upData = [
					'earning' => 0
				];
				$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
				$this->earningLock($address, 0); //关闭锁
				$data['earning'] = $checEarning;
				$textFile   = fopen("log/mining/".date("Y-m-d").".txt", "a");
				$textTime   = date("Y-m-d h:i:s");
				$aettos     = ($checkRow['amount'] / 1e18);
				$wtt_ttos   = $checEarning/1e18;
				$appendText = "领取收益--账户:{$address}\r\n映射AE:{$aettos}\r\n领取WTT:{$wtt_ttos}\r\n时间:{$textTime}\r\n\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
			} else {
				$this->earningLock($address, 0); //关闭锁
			}
		} else {
			return $this->DisposeModel-> wetJsonRt(406, 'error_earning');
		}
		return $this->DisposeModel-> wetJsonRt($code, 'success', $data);
	}

	public function checkMapping($address)
	{//映射信息查询
		$isMapState = $this->ValidModel-> isMapState($address);
		if (!$isMapState) {
			$totalAE = $this->getTotalAE();
			$data['state'] 	  = $isMapState;
			$data['total_ae'] = $totalAE;
			return $data;
		}

		$bsConfig     = (new ConfigModel())-> backendConfig();
		$blocksUrl    = $bsConfig['backendServiceNode'].'v3/key-blocks/current/height';
		@$getTop	  = file_get_contents($blocksUrl);
		$blocksJson   = (array) json_decode($getTop, true);
		$cuntnum      = 0;
		while (!$blocksJson && $cuntnum < 5) { //防止意外获取失败
			@$getTop 	= file_get_contents($blocksUrl);
			$blocksJson = (array) json_decode($getTop, true);
			$cuntnum++;
		}

		$blockHeight  	= (int)$blocksJson['height'];
		$mapInfo 		= $this->getUserMapInfo($address);
		$heightCheckOld = (int)$mapInfo['height_check'];
		$heightMap	    = (int)$mapInfo['height_map'];
		if (
				$mapInfo && //获取数据信息
				$isMapState && //已映射
				$blockHeight && //链上高度正常
				$heightCheckOld < $blockHeight &&  //小于链上高度
				$heightMap <> 0 //映射高度正常
			) {

			if (!$heightCheckOld || $heightCheckOld == 0) {
				$heightCheckOld	= $heightMap;
			}

			$accountsUrl  = $bsConfig['backendServiceNode'].'v3/accounts/'.$address;
			@$getBalance  = file_get_contents($accountsUrl);
			$accountsJson = (array) json_decode($getBalance, true);
			$cuntnum      = 0;
			while (!$accountsJson && $cuntnum < 5) { //防止意外获取失败
				@$getBalance = file_get_contents($accountsUrl);
				$accountsJson = (array) json_decode($getBalance, true);
				$cuntnum++;
			}
			$chainBalance = $accountsJson['balance'];  //链上金额
			$mapAmount 	  = $mapInfo['amount'];  //映射金额
			if ($chainBalance && $chainBalance < $mapAmount) {  //对比[映射]及[链上]金额
			//小黑屋判断
				$textFile   = fopen("log/mining/black-house-".date("Y-m-d").".txt", "a");
				$textTime   = date("Y-m-d h:i:s");
				$chainTtos  = ($chainBalance/1e18);
				$aettos  	= ($mapAmount/1e18);
				$appendText = "小黑屋--账户:{$address}\r\n链上AE:{$chainTtos}\r\n映射AE:{$aettos}\r\n时间:{$blockHeight}--{$textTime}\r\n\r\n";
				fwrite($textFile, $appendText);
				fclose($textFile);
				$upMapData = [
					'height_map'   => 0,
					'height_check' => 0,
					'earning' 	   => 0,
					'state'   	   => 0,
					'amount'  	   => 0
				];
				$this->db->table($this->wet_mapping)->where('address', $address)->update($upMapData);
				$this->db->table($this->wet_users)->where('address', $address)->update( ['is_map' => 0] );
				$blackHouse = true;
				$mapInfo    = $this->getUserMapInfo($address, $blackHouse);
				return $mapInfo;
			}

			$pastHeight = (int)($blockHeight - $heightCheckOld);
			$aettos    	= ($mapAmount / 1e18);
			$earningOld = $mapInfo['earning'];
			if ($pastHeight <= 960) {
				$earningNew = ( $earningOld + ($pastHeight * $aettos * 3e12) );
			} else {
				$earningNew = ( $earningOld + (960 * $aettos * 3e12) );
			}
			$upData = [
				'height_check' => $blockHeight,
				'earning' 	   => $earningNew
			];
			$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
			$mapInfo = $this->getUserMapInfo($address);
        }
		return $mapInfo;
	}

	private function getUserMapInfo($address, $blackHouse = false)
	{//获取用户映射信息
		$mapSql  = "SELECT address,
						height_map,
						height_check,
						state,
						amount,
						earning,
						earning_lock
					FROM $this->wet_mapping WHERE address = '$address' AND state = 1 LIMIT 1";
        $query  = $this->db->query($mapSql);
		$rowMap = $query->getRow();
		$totalAE = $this->getTotalAE();
		$data = [
			'address' 	   => $rowMap->address,
			'height_map'   => $rowMap->height_map,
			'height_check' => $rowMap->height_check,
			'black_house'  => $blackHouse,
			'state' 	   => $rowMap->state,
			'amount' 	   => $rowMap->amount,
			'earning' 	   => $rowMap->earning,
			'total_ae' 	   => $totalAE,
			'earning_lock' => $rowMap->earning_lock
		];
		return $data;
	}

	public function topTen()
	{//前10榜
		$sql    = "SELECT address, amount FROM $this->wet_mapping ORDER BY amount DESC LIMIT 10";
		$query  = $this->db-> query($sql);
		$getRes = $query->getResult();
		$data = [];
		foreach ($getRes as $row) {
			$address = $row->address;
			$amount  = $row->amount;
			$isData['userAddress'] = $address;
			$isData['amount'] 	   = $amount;
			if(isset($isData)) $detaila[] = $isData;
			$data = $detaila;
		}
		return $this->DisposeModel-> wetJsonRt(200,'success',$data);
	}

	public function adminOpenMapping($address)
	{//管理员开通映射账户
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		if($isAdmin && $address) {
			$this->UserModel-> userPut($address);
			$this->db->table($this->wet_users)->where('address', $address)->update( ['is_map' => '1'] );
			$textFile   = fopen("log/mining/open-mapping-".date("Y-m-d").".txt", "a");
			$appendText = "开通映射--{$akToken}--Admin\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			$data['isOpen'] = true;
		} else {
			$data['isOpen'] = false;
		}
		return $this->DisposeModel-> wetJsonRt(200, 'success', $data);
	}

	private function getTotalAE()
	{//统计映射AE总量
		$sql = "SELECT SUM(amount) AS total_ae FROM $this->wet_mapping WHERE state = '1'";
		$query = $this->db->query($sql);
		$total = $query->getRow();
		return $total->total_ae;
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

	private function earningLock($address, $value)
	{//改变锁状态
		$upData = [
			'earning_lock' => $value
		];
		$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
	}

}