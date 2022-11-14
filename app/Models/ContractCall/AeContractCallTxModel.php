<?php 
namespace App\Models\ContractCall;

use CodeIgniter\Model;
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
	private $DisposeModel;
	private $AeTokenConfig;
	private $GetAeknowModel;
	private $TokenEventModel;
	private $wet_temp;

	public function __construct(){
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->AeTokenConfig  = new AeTokenConfig();
		$this->GetAeknowModel = new GetAeknowModel();
		$this->TokenEventModel = new TokenEventModel();
		$this->wet_temp = "wet_temp";
    }

	public function txChainJsonRead($json)
	{//	链上Hash处理
		
		$hash = $json['hash'];
		//指定合同ID
		$tokenName = 'WTT';
		$contractId = $this->AeTokenConfig-> getContractId($tokenName);
		if ( $json['tx']['contract_id'] != $contractId ) {
			$this->deleteTemp($hash);
			$logMsg = "Token Contract_id 错误:{$hash}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_token');
		}

		//从 AEKnow 获取数据
		$aekJson = $this->GetAeknowModel-> tokenPayloadTx($hash);

		if ($aekJson['return_type'] != "ok") {
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(406,'error_return_type');
		}

		if ($aekJson['payload']) {
			$payload = (array) json_decode(base64_decode($aekJson['payload'], true), true);
			$aekJson['payload'] = $payload;
		}

		//版本检测
		$bsConfig = $this->ConfigModel-> backendConfig();
		$WeTrue  = $aekJson['payload']['WeTrue'];
		$require = $bsConfig['requireVersion'];
		$version = $this->DisposeModel-> versionCompare($WeTrue, $require);  //版本检测
		if (!$version)
		{  //版本号错误或低
			if(!$WeTrue){ //非WeTrue
				$this->deleteTemp($hash);
				$logMsg = "非WeTrue格式:{$hash}\r\n";
				$this->DisposeModel->wetFwriteLog($logMsg);
				return $this->DisposeModel-> wetJsonRt(406,'error_WeTrue');
			}
			$logMsg = "版本号异常:{$hash},版本号：{$WeTrue}\r\n";
			$this->DisposeModel->wetFwriteLog($logMsg);
			return $this->DisposeModel-> wetJsonRt(406,'error_version');
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

