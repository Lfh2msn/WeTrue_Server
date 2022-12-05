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

	public function openVipPut($json)
	{//开通vip 入库处理
		$hash		 = $json['txhash'];
		$senderId    = $json['sender_id'];
		$recipientId = $json['recipient_id'];
		$amount 	 = $json['amount'];
		$blockHeight = $json['block_height'];
		$textTime    = date("Y-m");
		$msgTime     = date("Y-m-d");

		$chainHeight = $this->GetAeChainModel->chainHeight($hash);  //获取链上高度
		if (empty($chainHeight)) {
			$logMsg = "{$msgTime}-获取链上高度失败hash: {$hash}";
			$logPath = "vip_open/error-{$textTime}";
			DisposeModel::wetFwriteLog($logMsg, $logPath);
			return DisposeModel::wetJsonRt(406, 'error_height');
        }
		$poorHeight	= ($chainHeight - $blockHeight);
		$opConfig = $this->OpenVipConfig->config();

		if (
			$opConfig['openVip'] &&
			$recipientId == $opConfig['openVipAddress'] &&
			$amount		 == $opConfig['openVipAmount'] &&
			$poorHeight  <= $opConfig['limitHeight']
		) {
			$isVipAccount = $this->ValidModel-> isVipAccount($senderId);
			if($isVipAccount) {
				$this->db->table($this->wet_users_vip)->where('address', $senderId)->update( ['is_vip' => 1] );
			} else {
				$insertData = [
					'address' => $senderId,
					'is_vip'  => 1
				];
				$this->db->table($this->wet_users_vip)->insert($insertData);
			}

			$wttAmount = $this->DisposeModel->bigNumber("div", $amount);
			$logMsg  = "{$msgTime}-开通成功-账户:{$senderId} 花费WTT:{$wttAmount} 高度:{$blockHeight} Hash:{$hash}";
			$logPath = "vip_open/open-vip-{$textTime}";
			DisposeModel::wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return DisposeModel::wetJsonRt(200, 'success');
		} else {
			$logMsg = "{$msgTime}-接收地址或金额或高度错误hash: {$hash}";
			$logPath = "vip_open/error-{$textTime}";
			DisposeModel::wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return DisposeModel::wetJsonRt(406, 'error');
		}
		
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

