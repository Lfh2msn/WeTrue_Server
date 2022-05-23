<?php 
namespace App\Models\ContractCall;

use App\Models\{
	DisposeModel,
	RewardModel
};

class TokenEventModel
{//AE智能合约TX处理模块

	public function __construct(){
		$this->DisposeModel = new DisposeModel();
		$this->RewardModel  = new RewardModel();
    }

	public function wttReward($json)
	{//	WTT 打赏
		$to_hash = $json['payload']['content'];
		$this->RewardModel-> rewardPut($json, $to_hash);
		return $this->DisposeModel-> wetJsonRt(200);
	}

}

