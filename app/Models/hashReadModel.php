<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	BloomModel,
	ValidModel,
	DeleteModel,
	ConfigModel,
	DisposeModel
};
use App\Models\Get\GetAeChainModel;
use App\Models\Content\{
	AeChainPutModel,
	AeSuperheroPutModel
};
use App\Models\ContractCall\AeContractCallTxModel;

class HashReadModel
{//链上hash入库Model

	public function __construct(){
		$this->BloomModel  = new BloomModel();
		$this->AeChainPutModel = new AeChainPutModel();
		$this->AeSuperheroPutModel  = new AeSuperheroPutModel();
		$this->AeContractCallTxModel = new AeContractCallTxModel();
		$this->wet_temp = "wet_temp";
    }

	public function split($hash, $chainId){
	//上链hash入库
		$repeatHash = ValidModel::isTempHash($hash); //重复检测
		if ($repeatHash) {
			return DisposeModel::wetJsonRt(406,'error_repeat');
		}
		//写入临时缓存
		$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_chain_id) VALUES ('$hash', '$chainId')";
		ComModel::db()->query($insertTempSql);
		return DisposeModel::wetJsonRt(200);
	}

	public function hashEvent(){
	//上链内容出库事件
		$bsConfig 	= ConfigModel::backendConfig();
		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '5 D'";
		ComModel::db()->query($delTempSql);

		$tpSql   = "SELECT tp_hash FROM $this->wet_temp ORDER BY tp_time DESC LIMIT 30";
		$tpquery = ComModel::db()-> query($tpSql);
		$result  = $tpquery-> getResult();
		foreach ($result as $row) {
			$tp_hash = $row-> tp_hash;
			$json 	 = GetAeChainModel::transactions($tp_hash);
			if (!$json) {
				DisposeModel::wetFwriteLog("未获取到链上数据:{$tp_hash}");
				continue;
			}

			if ($json['tx']['type'] == 'ContractCallTx'){
				//合约呼叫 转到 合约处理
				$this->AeContractCallTxModel-> txChainJsonRead($json);
				continue;
			}

			$sender = $json['tx']['sender_id'];
			/* 临时屏蔽
			$isContinue = $this->BloomModel-> userCheck($sender); //黑名单账户检查
			if (!$isContinue) {
				DisposeModel::wetFwriteLog("黑名单账户:{$sender}");
				continue;
			}
			*/
			$isBloomAddress = ValidModel::isBloomAddress($sender);
			if ($isBloomAddress) {
				DisposeModel::wetFwriteLog("被bloom过滤账户:{$sender},Hash:{$tp_hash}");
				$this->deleteTemp($tp_hash);  //删除临时缓存
				DeleteModel::deleteAll($sender); //删除账户
				continue;
			}

			if ( //过滤无效预设钱包
				$json['tx']['recipient_id'] != $bsConfig['receivingAccount'] || 
				$json['tx']['type'] != 'SpendTx' || 
				$json['tx']['payload'] == null || 
				$json['tx']['payload'] == "ba_Xfbg4g=="
			){
				$this->deleteTemp($tp_hash);  //删除临时缓存
				DisposeModel::wetFwriteLog("错误类型:{$tp_hash}");
				continue;
			}
			$this->AeChainPutModel->decodeContent($json);
		}

		/*
			* 抓取超级英雄数据，及写入
			* 服务器9-12小时=中国时间22--00点执行
			* 服务器21-23小时=中国时间10--14点执行
		*/
		$currentHour = date('H');
		if (
			$currentHour == 2
			|| $currentHour == 4
			|| $currentHour == 6
			|| $currentHour == 8
			|| $currentHour == 10
			|| $currentHour == 12
			|| $currentHour == 14
			|| $currentHour == 16
			|| $currentHour == 18
			|| $currentHour == 20
			|| $currentHour == 22
			|| $currentHour == 0
		) $this->AeSuperheroPutModel-> putContent(1);
		return DisposeModel::wetJsonRt(200);
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		ComModel::db()->query($delete);
	}

}

