<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	AecliModel,
	ValidModel,
	ConfigModel,
	DisposeModel
};
use App\Models\Get\GetAeknowModel;

class MiningModel extends ComModel
{//挖矿Model

	public function __construct()
	{
		parent::__construct();
		$this->AecliModel   = new AecliModel();
		$this->UserModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->GetAeknowModel = new GetAeknowModel();
		$this->wet_mapping  = "wet_mapping";
		$this->wet_temp     = "wet_temp";
    }

	public function inMapping($address, $amount)
	{//用户映射挖矿
		$bsConfig   = (new ConfigModel())-> backendConfig();
		$mappingWTT = $bsConfig['mappingWTT'];
		if (!$mappingWTT) {
			return DisposeModel::wetJsonRt(406, 'close_mapping');
		}

		$isAddress    = DisposeModel::checkAddress($address);
		$isVipAddress = $this->ValidModel-> isVipAddress($address);
		if (!$isAddress && !$isVipAddress) {
			return DisposeModel::wetJsonRt(406, 'did_not_open_mapping');
		}

		$isMapping = $this->ValidModel-> isMapState($address);
		if ($isMapping) {
			return DisposeModel::wetJsonRt(406, 'repeat_mapping');
		}

		$accountsBalance = $this->GetAeknowModel->accountsBalance($address);  //查询链上金额
		if (empty($getJson) && $accountsBalance <= $amount) {
        	return DisposeModel::wetJsonRt(406, 'error_amount');
        }
	
		$blockHeight = $this->GetAeknowModel->chainHeight();  //获取链上高度
		if (empty($blockHeight)) {
        	return DisposeModel::wetJsonRt(406, 'get_block_height_error');
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
		$aettos  = $this->DisposeModel->bigNumber("div", $amount);
		$logMsg  = "开启映射--账户:{$address}\r\n映射AE:{$aettos}\r\n时间:{$blockHeight}--".date('Y-m-d')."\r\n";
		$logPath = "mining/".date('Y-m-d');
		$this->DisposeModel->wetFwriteLog($logMsg, $logPath);

		$data['state'] = true;
		return DisposeModel::wetJsonRt(200, 'success', $data);
	}

	public function unMapping($address)
	{//用户解除映射
		$checkRow = $this-> checkMapping($address);
		if (!$checkRow) {
			return DisposeModel::wetJsonRt(406, 'error_unknown');
		}

		if ($checkRow['state'] == 0) {
			$data['state'] = false;
			return DisposeModel::wetJsonRt(406, 'no_mapping', $data);
		}

		$checEarning = $checkRow['earning'];
		$earnLock    = $checkRow['earning_lock'];
		if ($checEarning >= 1e17 && $earnLock == 0) {
			$this->earningLock($address, 1); //打开锁
			$hash = $this->AecliModel-> spendWTT($address, $checEarning);
			$code = DisposeModel::checkAddress($hash) ? 200 : 406;
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
			$ymdhTime = date("Y-m-d h:i:s");
			$aettos   = $this->DisposeModel->bigNumber("div", $checkRow['amount']);
			$wtt_ttos = $this->DisposeModel->bigNumber("div", $checEarning);
			$logMsg   = "解除映射--账户:{$address}\r\n映射AE:{$aettos}\r\n领取WTT:{$wtt_ttos}\r\n时间:{$ymdhTime}\r\n";
			$logPath  = "mining/".date('Y-m-d');
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$msg = 'success';
		}
		return DisposeModel::wetJsonRt($code, $msg, $data);
	}

	public function getEarning($address)
	{//用户领取收益
		$checkRow = $this-> checkMapping($address);
		if (!$checkRow) {
			return DisposeModel::wetJsonRt(406,'error_unknown1',[]);
		}

		if ($checkRow['state'] == 0) {
			$data['state'] = false;
			return DisposeModel::wetJsonRt(406,'no_mapping',$data);
		}

		$checEarning = $checkRow['earning'];
		$earnLock    = $checkRow['earning_lock'];
		if ($checEarning >= 1e17 && $earnLock == 0) {
			$this->earningLock($address, 1); //打开锁
			$hash = $this->AecliModel-> spendWTT($address, $checEarning);
			$code = DisposeModel::checkAddress($hash) ? 200 : 406;
			if ($code == 200) {
				$upData = [
					'earning' => 0
				];
				$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
				$this->earningLock($address, 0); //关闭锁
				$data['earning'] = $checEarning;
				$ymdhTime = date("Y-m-d h:i:s");
				$aettos   = $this->DisposeModel->bigNumber("div", $checkRow['amount']);
				$wtt_ttos = $this->DisposeModel->bigNumber("div", $checEarning);
				$logMsg   = "领取收益--账户:{$address}\r\n映射AE:{$aettos}\r\n领取WTT:{$wtt_ttos}\r\n时间:{$ymdhTime}\r\n";
				$logPath  = "mining/".date('Y-m-d');
				$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			} else {
				$this->earningLock($address, 0); //关闭锁
			}
		} else {
			return DisposeModel::wetJsonRt(406, 'error_earning');
		}
		return DisposeModel::wetJsonRt($code, 'success', $data);
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

		$bsConfig   = (new ConfigModel())-> backendConfig();
		$mappingWTT = $bsConfig['mappingWTT']; //映射挖矿开启状态
		if (!$mappingWTT) {
			$upMapData = [
				'height_map'   => 0,
				'height_check' => 0,
				'earning' 	   => 0,
				'state'   	   => 0,
				'amount'  	   => 0
			];
			$this->db->table($this->wet_mapping)->where('address', $address)->update($upMapData);
			$mapInfo = $this->getUserMapInfo($address);
			return $mapInfo;
		}
		
		$blockHeight    = $this->GetAeknowModel->chainHeight($hash);  //获取链上高度
		$mapInfo 		= $this->getUserMapInfo($address);
		$heightCheckOld = $mapInfo['height_check'];
		$heightMap	    = $mapInfo['height_map'];
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

			$accountsBalance = $this->GetAeknowModel->accountsBalance($address);  //查询链上金额
			$mapAmount = $mapInfo['amount'];  //映射金额
			if ($accountsBalance && $accountsBalance < $mapAmount) {  //对比[映射]及[链上]金额
			//小黑屋判断
				$ymdhTime  = date("Y-m-d h:i:s");
				$chainTtos = $this->DisposeModel->bigNumber("div", $accountsBalance);
				$aettos    = $this->DisposeModel->bigNumber("div", $mapAmount);
				$logMsg    = "小黑屋--账户:{$address}\r\n链上AE:{$chainTtos}\r\n映射AE:{$aettos}\r\n时间:{$blockHeight}--{$ymdhTime}\r\n";
				$logPath   = "mining/black-house-".date('Y-m-d');
				$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
				$upMapData = [
					'height_map'   => 0,
					'height_check' => 0,
					'earning' 	   => 0,
					'state'   	   => 0,
					'amount'  	   => 0
				];
				$this->db->table($this->wet_mapping)->where('address', $address)->update($upMapData);
				$this->db->table('wet_users_vip')->where('address', $address)->update( ['is_vip' => 0] );
				$blackHouse = true;
				$mapInfo    = $this->getUserMapInfo($address, $blackHouse);
				return $mapInfo;
			}

			$pastHeight = $this->DisposeModel->bigNumber("sub", $blockHeight, $heightCheckOld);
			$aettos    	= $this->DisposeModel->bigNumber("div", $mapAmount);
			$earningOld = $mapInfo['earning'];
			if ($pastHeight <= 960) {
				$mapAettos  = $this->DisposeModel->bigNumber("mul", $pastHeight, $aettos);
				$eaAettos   = $this->DisposeModel->bigNumber("mul", $mapAettos, 3000000000000);
				$earningNew = $this->DisposeModel->bigNumber("add", $earningOld, $eaAettos);
			} else {
				$mapAettos  = $this->DisposeModel->bigNumber("mul", 960, $aettos);
				$eaAettos   = $this->DisposeModel->bigNumber("mul", $mapAettos, 3000000000000);
				$earningNew = $this->DisposeModel->bigNumber("add", $earningOld, $eaAettos);
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
		return DisposeModel::wetJsonRt(200,'success',$data);
	}

	public function adminOpenMapping($address)
	{//管理员开通映射账户
		$akToken   = $_SERVER['HTTP_KEY'];
		$isAdmin   = $this->ValidModel-> isAdmin($akToken);
		if($isAdmin && $address) {
			$this->UserModel-> userPut($address);
			$this->db->table('wet_users_vip')->where('address', $address)->update( ['is_vip' => '1'] );
			$logMsg  = "开通映射--{$akToken}--Admin";
			$logPath = "mining/open-mapping-".date('Y-m-d');
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$data['isOpen'] = true;
		} else {
			$data['isOpen'] = false;
		}
		return DisposeModel::wetJsonRt(200, 'success', $data);
	}

	private function getTotalAE()
	{//统计映射AE总量
		$sql = "SELECT SUM(amount) AS total_ae FROM $this->wet_mapping WHERE state = '1'";
		$query = $this->db->query($sql);
		$total = $query->getRow();
		return $total->total_ae;
	}

	private function earningLock($address, $value)
	{//改变锁状态
		$upData = [
			'earning_lock' => $value
		];
		$this->db->table($this->wet_mapping)->where('address', $address)->update($upData);
	}

}