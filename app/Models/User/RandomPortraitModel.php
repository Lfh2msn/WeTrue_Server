<?php namespace App\Models\User;

use App\Models\{
	ComModel,
	ValidModel,
	ConfigModel,
	DisposeModel
};
use App\Models\Config\OpenVipConfig;
use App\Models\Get\{
	GetAeChainModel,
	GetAeknowModel
};

class RandomPortraitModel extends ComModel
{//用户随机头像

	public function __construct()
	{
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->OpenVipConfig = new OpenVipConfig();
		$this->ValidModel    = new ValidModel();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->GetAeknowModel  = new GetAeknowModel();
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
			$this->decode($tp_hash);
		}
	}

	public function decode($hash)
	{//新开户-解码开通
		$textTime = date("Y-m");
		$aeknowApiJson = $this->GetAeknowModel->tokenTx($hash);
		if (empty($aeknowApiJson)) {
			$logMsg = "开通失败获取AEKnow-API错误: {$hash}\r\n\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return $this->DisposeModel-> wetJsonRt(406, 'error_unknown');
        }

		$chainHeight = $this->GetAeChainModel->chainHeight($hash);  //获取链上高度
		if (empty($chainHeight)) {
			$logMsg = "获取链上高度失败hash: {$hash}\r\n\r\n";
			$logPath = "log/vip_open/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return $this->DisposeModel-> wetJsonRt(406, 'error_height');
        }

		$senderId    = $aeknowApiJson['sender_id'];
		$recipientId = $aeknowApiJson['recipient_id'];
		$amount 	 = $aeknowApiJson['amount'];
		$returnType  = $aeknowApiJson['return_type'];
		$blockHeight = $aeknowApiJson['block_height'];
		$contractId  = $aeknowApiJson['contract_id'];
		$poorHeight	 = ($chainHeight - $blockHeight);

		if ($return_type == "revert") {  //无效转账
			$this->deleteTemp($hash);
			return;
		}

		$opConfig = $this->OpenVipConfig->config();
		if (
			$recipientId == $opConfig['openVipAddress'] &&
			$amount		 == $opConfig['openVipAmount'] &&
			$contractId  == $opConfig['openTokenAddress'] &&
			$poorHeight  <= 480 &&
			$returnType  == "ok"
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
			$logMsg  = "开通VIP成功-账户:{$senderId}\r\n花费WTT:{$wttAmount}\r\n高度:{$blockHeight}\r\nHash:{$hash}\r\n\r\n";
			$logPath = "log/vip_open/open-vip-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(200, 'success');
		}
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

