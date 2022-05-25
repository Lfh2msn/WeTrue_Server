<?php namespace App\Models\User;

use App\Models\{
	ComModel,
	ValidModel,
	DisposeModel
};
use App\Models\Config\{
	RandomPortraitConfig,
	AeTokenConfig
};
use App\Models\Get\{
	GetAeChainModel
};

class RandomPortraitModel extends ComModel
{//随机头像

	public function __construct()
	{
		parent::__construct();
		$this->ValidModel = new ValidModel();
		$this->DisposeModel = new DisposeModel();
		$this->AeTokenConfig = new AeTokenConfig();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->RandomPortraitConfig = new RandomPortraitConfig();
		$this->wet_temp  = "wet_temp";
		$this->wet_users = "wet_users";
		$this->wet_random_portrait = "wet_random_portrait";
    }


	public function randomPortraitPut($json)
	{//随机头像 入库处理
		$hash	  = $json['txhash'];
		$sendId   = $json['sender_id'];
		$recId    = $json['recipient_id'];
		$amount   = $json['amount'];
		$bHeight  = $json['block_height'];
		$textTime = date("Y-m");

		$isHash = $this->ValidModel-> isRandomPortraitHash($hash);
		if ($isHash) {
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(406, 'error_repeat');
		}

		$isVipAddress = $this->ValidModel-> isVipAddress($sendId);
		if (!$isVipAddress) {
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(406, 'error_noVip');
		}

		$cHeight = $this->GetAeChainModel->chainHeight($hash);  //获取链上高度
		if (empty($cHeight)) {
			$logMsg = "获取链上高度失败hash: {$hash}\r\n";
			$logPath = "log/random_portrait/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return $this->DisposeModel-> wetJsonRt(406, 'error_height');
        }
		$pHeight = ($cHeight - $bHeight);

		$rpConfig = $this->RandomPortraitConfig-> config();
		if (
			$rpConfig['randomPortrait'] &&
			$recId   == $rpConfig['recAddress'] &&
			$amount	 == $rpConfig['recAmount'] &&
			$pHeight <= $rpConfig['limitHeight']
		) {
			//开始创建随机数头像
			$random = $this->DisposeModel-> randBase58();

			//更新头像
			$this->db->table($this->wet_users)->where('address', $sendId)->update( ['portrait' => $random] );

			//入库头像列表
			$insertData = [
				'address' => $sendId,
				'random'  => $random,
				'hash'    => $hash
			];
			$this->db->table($this->wet_random_portrait)->insert($insertData);
			
			$wttAmount = $this->DisposeModel->bigNumber("div", $amount);
			$logMsg  = "成功-账户:{$sendId} 花费WTT:{$wttAmount} 高度:{$bHeight} Hash:{$hash}\r\n";
			$logPath = "log/random_portrait/open-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(200);
		} else {
			$logMsg = "接收地址或金额或高度错误hash: {$hash}\r\n";
			$logPath = "log/random_portrait/error-{$textTime}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			$this->deleteTemp($hash);
			return $this->DisposeModel-> wetJsonRt(406, 'error');
		}
	}

	private function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		$this->db->query($delete);
	}

}

