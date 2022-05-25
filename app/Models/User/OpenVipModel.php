<?php namespace App\Models\User;

use App\Models\{
	ComModel,
	ValidModel,
	DisposeModel
};
use App\Models\Config\{
	OpenVipConfig,
	AeTokenConfig
};
use App\Models\Get\{
	GetAeChainModel,
	GetAeknowModel
};

class OpenVipModel extends ComModel
{//用户开通VIP Model

	public function __construct()
	{
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->OpenVipConfig = new OpenVipConfig();
		$this->ValidModel    = new ValidModel();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->GetAeknowModel  = new GetAeknowModel();
		$this->AeTokenConfig = new AeTokenConfig();
		$this->wet_temp      = "wet_temp";
		$this->wet_users_vip = "wet_users_vip";
    }

	public function openAccount($hash, $address)
	{//新开户
		$tp_type = "openvip";
		$isTempHash = $this->ValidModel-> isTempHash($hash);
		if ($isTempHash) {
			echo $this->DisposeModel-> wetJsonRt(406, 'error_repeat');
		} else {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_sender_id, tp_type) VALUES ('$hash', '$address', '$tp_type')";
			$this->db->query($insertTempSql);
			echo $this->DisposeModel-> wetJsonRt(200, 'success');
		}

		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '5 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$tempSql = "SELECT tp_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC LIMIT 30";
		$tempQy  = $this->db-> query($tempSql);
		$tempRes = $tempQy->getResult();
		foreach ($tempRes as $row) {
			$tp_hash = $row->tp_hash;
			$this->getOpenAccount($tp_hash);
		}
	}


	public function getOpenAccount($hash)
	{//获取开通数据
		$textTime = date("Y-m");
		$json = $this->GetAeknowModel->tokenPayloadTx($hash);
		if (empty($json)) {
			$logMsg = "获取AEKnow-API错误: {$hash}\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return $this->DisposeModel-> wetJsonRt(406, 'error_unknown');
        }

		$tokenName   = 'WTT';
		$contractId  = $this->AeTokenConfig-> getContractId($tokenName);
		$return_type = $json['return_type'];
		$contract_id = $json['contract_id'];

		if ($contract_id != $contractId || $return_type != 'ok') {
			$this->deleteTemp($hash);
			$logMsg = "开通合约id或状态错误--hash: {$hash}\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return;
		}

		$this->openVipPut($json);
	}

	public function openVipPut($json)
	{//开通vip 入库处理
		$hash		 = $json['txhash'];
		$senderId    = $json['sender_id'];
		$recipientId = $json['recipient_id'];
		$amount 	 = $json['amount'];
		$blockHeight = $json['block_height'];
		$textTime    = date("Y-m");

		$chainHeight = $this->GetAeChainModel->chainHeight($hash);  //获取链上高度
		if (empty($chainHeight)) {
			$logMsg = "获取链上高度失败hash: {$hash}\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return $this->DisposeModel-> wetJsonRt(406, 'error_height');
        }
		$poorHeight	= ($chainHeight - $blockHeight);
		$opConfig = $this->OpenVipConfig->config();

		if (
			$recipientId == $opConfig['openVipAddress'] &&
			$amount		 == $opConfig['openVipAmount'] &&
			$poorHeight  <= $opConfig['limitHeight']
		) {
			$isVipAddress = $this->ValidModel-> isVipAccount($senderId);
			if($isVipAddress) {
				$this->db->table($this->wet_users_vip)->where('address', $senderId)->update( ['is_vip' => 1] );
			} else {
				$insertData = [
					'address' => $senderId,
					'is_vip'  => 1
				];
				$this->db->table($this->wet_users_vip)->insert($insertData);
			}

			$wttAmount = $this->DisposeModel->bigNumber("div", $amount);
			$logMsg  = "开通成功-账户:{$senderId} 花费WTT:{$wttAmount} 高度:{$blockHeight} Hash:{$hash}\r\n";
			$logPath = "log/vip_open/open-vip-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(200, 'success');
		} else {
			$logMsg = "接收地址或金额或高度错误hash: {$hash}\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
		}
		return $this->DisposeModel-> wetJsonRt(406, 'error');
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

