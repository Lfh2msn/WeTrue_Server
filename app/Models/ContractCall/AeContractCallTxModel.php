<?php 
namespace App\Models\ContractCall;

use CodeIgniter\Model;
use Config\Database;
use App\Models\Config\AeTokenConfig;
use App\Models\Get\GetAeknowModel;
use App\Models\{
	DisposeModel,
	ConfigModel
};
use App\Models\ContractCall\TokenEventModel;

class AeContractCallTxModel extends Model
{//AE智能合约TX处理模块

	private $ConfigModel;
	private $GetAeknowModel;
	private $TokenEventModel;
	private $wet_temp;

	public function __construct(){
		$this->db = Database::connect('default');
		$this->GetAeknowModel = new GetAeknowModel();
		$this->TokenEventModel = new TokenEventModel();
		$this->wet_temp = "wet_temp";
    }

	public function txChainJsonRead($json)
	{//	链上Hash处理
		
		$hash = $json['hash'];
		//指定合同ID
		$tokenName = 'WTT';
		$contractId = AeTokenConfig::getContractId($tokenName);
		if ( $json['tx']['contract_id'] != $contractId ) {
			$this->deleteTemp($hash);
			DisposeModel::wetFwriteLog("Token Contract_id 错误:{$hash}");
			return DisposeModel::wetJsonRt(406,'error_token');
		}

		//从 AEKnow 获取数据
		$aekJson = $this->GetAeknowModel-> tokenPayloadTx($hash);

		if ($aekJson['return_type'] != "ok") {
			$this->deleteTemp($hash);
			DisposeModel::wetFwriteLog("Token Tx return_type 错误:{$hash}");
			return DisposeModel::wetJsonRt(406,'error_return_type');
		}

		if ($aekJson['payload']) {
			$payload = (array) json_decode(base64_decode(base64_decode($aekJson['payload'], true)), true);
			$aekJson['payload'] = $payload;
		}

		//版本检测
		$bsConfig = ConfigModel::backendConfig();
		$WeTrue  = $aekJson['payload']['WeTrue'];
		$require = $bsConfig['requireVersion'];
		$version = DisposeModel::versionCompare($WeTrue, $require);  //版本检测
		if (!$version)
		{  //版本号错误或低
			if(!$WeTrue){ //非WeTrue
				$this->deleteTemp($hash);
				DisposeModel::wetFwriteLog("非WeTrue格式:{$hash}");
				return DisposeModel::wetJsonRt(406,'error_WeTrue');
			}
			DisposeModel::wetFwriteLog("版本号异常:{$hash},版本号：{$WeTrue}");
			return DisposeModel::wetJsonRt(406,'error_version');
		}

		if ($aekJson['payload']['type']) {
			return $this->TokenEventModel-> event($aekJson);
		}

	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

