<?php 
namespace App\Models\ContractCall;

use App\Models\{
	DisposeModel,
	RewardModel
};
use App\Models\User\{
	OpenVipModel,
	RandomPortraitModel
};

class TokenEventModel
{//AE智能合约TX处理模块

	public function __construct(){
		$this->DisposeModel = new DisposeModel();
		$this->RewardModel  = new RewardModel();
		$this->OpenVipModel = new OpenVipModel();
		$this->RandomPortraitModel = new RandomPortraitModel();
    }

	public function event($json)
	{//	事件处理
		if ($json['payload']['type'] == 'reward') {
			return $this-> wttReward($json);
		}

		if ($json['payload']['type'] == 'openVip') {
			return $this-> wttOpenVip($json);
		}

		if ($json['payload']['type'] == 'randomPortrait') {
			return $this-> wttRandomPortrait($json);
		}

		return $this->DisposeModel-> wetJsonRt(406, 'error_type');
	}

	public function wttReward($json)
	{//	WTT 打赏
		$to_hash = $json['payload']['content'];
		$this->RewardModel-> rewardPut($json, $to_hash);
		return $this->DisposeModel-> wetJsonRt(200);
	}

	public function wttOpenVip($json)
	{//	WTT 开通VIP
		$this->OpenVipModel-> openVipPut($json);
		return $this->DisposeModel-> wetJsonRt(200);
	}

	public function wttRandomPortrait($json)
	{//	WTT 随机头像
		$this->RandomPortraitModel-> randomPortraitPut($json);
		return $this->DisposeModel-> wetJsonRt(200);
	}
	

}

