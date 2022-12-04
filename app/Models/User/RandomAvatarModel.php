<?php namespace App\Models\User;

use App\Models\{
	ComModel,
	ValidModel,
	DisposeModel
};
use App\Models\Config\{
	RandomAvatarConfig,
	AeTokenConfig,
	ActiveConfig
};
use App\Models\Get\{
	GetAeChainModel
};

class RandomAvatarModel extends ComModel
{//随机头像

	public function __construct()
	{
		parent::__construct();
		$this->ValidModel = new ValidModel();
		$this->DisposeModel = new DisposeModel();
		$this->ActiveConfig = new ActiveConfig();
		$this->AeTokenConfig = new AeTokenConfig();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->RandomAvatarConfig = new RandomAvatarConfig();
		$this->wet_temp  = "wet_temp";
		$this->wet_users = "wet_users";
		$this->wet_random_avatar = "wet_random_avatar";
    }


	public function randomAvatarPut($json)
	{//随机头像 入库处理
		$hash	  = $json['txhash'];
		$sendId   = $json['sender_id'];
		$recId    = $json['recipient_id'];
		$amount   = $json['amount'];
		$bHeight  = $json['block_height'];
		$textTime = date("Y-m");
		$msgTime  = date("Y-m-d");

		$isHash = $this->ValidModel-> isRandomAvatarHash($hash);
		if ($isHash) {
			$this->deleteTemp($hash);
			return DisposeModel::wetJsonRt(406, 'error_repeat');
		}

		$isVipAddress = $this->ValidModel-> isVipAddress($sendId);
		if (!$isVipAddress) {
			$this->deleteTemp($hash);
			return DisposeModel::wetJsonRt(406, 'error_noVip');
		}

		$cHeight = $this->GetAeChainModel->chainHeight($hash);  //获取链上高度
		if (empty($cHeight)) {
			$logMsg = "{$msgTime}-获取链上高度失败hash: {$hash}";
			$logPath = "random_avatar/error-{$textTime}";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return DisposeModel::wetJsonRt(406, 'error_height');
        }
		$pHeight = ($cHeight - $bHeight);

		$raConfig = $this->RandomAvatarConfig-> config();
		if (
			$raConfig['randomAvatar'] &&
			$recId   == $raConfig['recAddress'] &&
			$amount	 == $raConfig['recAmount'] &&
			$pHeight <= $raConfig['limitHeight']
		) {
			//开始创建随机数头像
			$random = DisposeModel::randBase58();
			//更新头像
			$this->db->table($this->wet_users)->where('address', $sendId)->update( ['avatar' => $random] );
			//更新活跃度
			$ActiveConfig = $this->ActiveConfig-> config();
			$avatarActive = $bsConfig['avatarActive'];
			$updateSql = "UPDATE $this->wet_users SET uactive = uactive + '$avatarActive' WHERE address = '$sendId'";
			$this->db->query($updateSql);
			//入库头像列表
			$insertData = [
				'address' => $sendId,
				'random'  => $random,
				'hash'    => $hash
			];
			$this->db->table($this->wet_random_avatar)->insert($insertData);
			
			$wttAmount = $this->DisposeModel->bigNumber("div", $amount);
			$logMsg  = "{$msgTime}-成功-账户:{$sendId} 花费WTT:{$wttAmount} 高度:{$bHeight} Hash:{$hash}";
			$logPath = "random_avatar/open-{$textTime}";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return DisposeModel::wetJsonRt(200);
		} else {
			$logMsg = "{$msgTime}-接收地址或金额或高度错误hash: {$hash}";
			$logPath = "random_avatar/error-{$textTime}";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
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

