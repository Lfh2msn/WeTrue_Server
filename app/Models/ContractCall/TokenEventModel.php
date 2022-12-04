<?php 
namespace App\Models\ContractCall;

use App\Models\{
	DisposeModel,
	RewardModel
};
use App\Models\User\{
	OpenVipModel,
	RandomAvatarModel
};

class TokenEventModel
{//AE智能合约TX处理模块

	public function __construct(){
		$this->RewardModel  = new RewardModel();
		$this->OpenVipModel = new OpenVipModel();
		$this->RandomAvatarModel = new RandomAvatarModel();
    }

	public function event($json)
	{//	事件处理
		$payloadType = $json['payload']['type'];

		if ($payloadType == 'reward') {
		// WTT 打赏
			return $this->RewardModel-> rewardPut($json);
		}

		if ($payloadType == 'open_vip') {
		// WTT 开通VIP
			return $this->OpenVipModel-> openVipPut($json);
		}

		if ($payloadType == 'random_avatar') {
		// WTT 随机头像
			return $this->RandomAvatarModel-> randomAvatarPut($json);
		}

		return DisposeModel::wetJsonRt(406, 'error_type');
	}

}

