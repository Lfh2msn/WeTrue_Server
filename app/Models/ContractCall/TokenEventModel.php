<?php 
namespace App\Models\ContractCall;

use App\Models\{
	DisposeModel,
	RewardModel
};
use App\Models\User\OpenVipModel;

class TokenEventModel
{//AE智能合约TX处理模块

	public function __construct(){
		$this->DisposeModel = new DisposeModel();
		$this->RewardModel  = new RewardModel();
		$this->OpenVipModel = new OpenVipModel();
    }

	public function event($json)
	{//	事件处理
		if ($json['payload']['type'] == 'reward') {
			$this-> wttReward($json);
		}

		if ($json['payload']['type'] == 'openVip') {
			$this-> wttOpenVip($json);
		}
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
	

}

