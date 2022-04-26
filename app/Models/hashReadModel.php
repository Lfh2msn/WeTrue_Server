<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	GetModel,
	BloomModel,
	ValidModel,
	DeleteModel,
	ConfigModel,
	DisposeModel,
	SuperheroModel,
	AeChainContentModel
};

class HashReadModel extends Model {
//链上hash入库Model

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->GetModel   = new GetModel();
		$this->BloomModel = new BloomModel();
		$this->ValidModel = new ValidModel();
		$this->DeleteModel = new DeleteModel();
		$this->ConfigModel = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->SuperheroModel = new SuperheroModel();
		$this->AeChainContentModel = new AeChainContentModel();
		$this->wet_temp = "wet_temp";
    }

	public function split($hash, $await=false, $chainId=457)
	{//上链内容入库
		$tp_type    = "common";
		$isTempHash = $this->ValidModel-> isTempHash($hash);
		$bsConfig 	= $this->ConfigModel-> backendConfig();
		if (!$isTempHash) {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_type, tp_chain_id) VALUES ('$hash', '$tp_type', '$chainId')";
			$this->db->query($insertTempSql);
			if (!$await) echo $this->DisposeModel-> wetJsonRt(200);
		} else {
			if (!$await) echo $this->DisposeModel-> wetJsonRt(406,'error_repeat');
		}

		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '1 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$tpSql   = "SELECT tp_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC LIMIT 30";
		$tpquery = $this->db-> query($tpSql);
		$result  = $tpquery-> getResult();
		foreach ($result as $row) {
			$tp_hash  = $row-> tp_hash;
			$json 	  = $this->GetModel->getTransactions($tp_hash);
			if (!$json) {
				$logMsg = "链上未获取到数据:{$tp_hash}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
				continue;
			}
			$sender = $json['tx']['sender_id'];
			$isContinue = $this->BloomModel-> userCheck($sender); //黑名单账户检查
			if (!$isContinue) continue;
			$isBloomAddress = $this->ValidModel ->isBloomAddress($sender);
			if ($isBloomAddress) {
				$logMsg = "被bloom过滤账户:{$tp_hash}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
				$this->deleteTemp($hash);  //删除临时缓存
				$this->DeleteModel-> deleteAll($sender); //删除账户
				continue;
			}

			if ( empty(  //过滤无效预设钱包
					$json['tx']['recipient_id'] == $bsConfig['receivingAccount'] || 
					$json['tx']['type'] == 'SpendTx' || 
					$json['tx']['payload'] == null || 
					$json['tx']['payload'] == "ba_Xfbg4g=="
				) ){
					$this->deleteTemp($hash);  //删除临时缓存
					$logMsg = "错误类型:{$hash}\r\n";
					$this->DisposeModel->wetFwriteLog($logMsg);
					continue;
			}
			$this->AeChainContentModel->decodeContent($json);
			/**
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
				) {
				$this->SuperheroModel-> getContent(1);
			}
		}
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

