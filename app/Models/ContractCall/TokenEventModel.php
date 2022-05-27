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
		$this->RandomAvatarModel = new RandomAvatarModel();
    }

	public function event($json)
	{//	事件处理
		$payloadType = $json['payload']['type'];

		if ($payloadType == 'reward') {
		// WTT 打赏
			$to_hash = $json['payload']['content'];
			return $this->RewardModel-> rewardPut($json, $to_hash);
		}

		if ($payloadType == 'open_vip' || $payloadType == 'openVip') {
		// WTT 开通VIP
			return $this->OpenVipModel-> openVipPut($json);
		}

		if ($payloadType == 'random_avatar' || $payloadType == 'randomPortrait') {
		// WTT 随机头像
			return $this->RandomAvatarModel-> randomAvatarPut($json);
		}

		return $this->DisposeModel-> wetJsonRt(406, 'error_type');
	}

}

