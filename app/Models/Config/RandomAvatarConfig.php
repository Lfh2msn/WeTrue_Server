<?php namespace App\Models\Config;

use App\Models\Config\AeTokenConfig;

class RandomAvatarConfig {
//随机头像配置

    public function config()
	{//配置
		$tokenName  = 'WTT';
		$contractId = (NEW AeTokenConfig())-> getContractId($tokenName);
		
		return array(
			'randomPortrait' => true, //可用状态 -- 即将废弃(2.5.0)(APP 2.9.5)
			'randomAvatar'   => true, //可用状态
			'recAddress'     => 'ak_2afGvLkUTwdCixNLxKVbpmtGqDMSGVnU2orSTChNqKRcYk7xvV', //收款地址
			'recAmount'      => "10000000000000000000", //费用10WTT = 10000000000000000000
			'recToken'       => $contractId, //开通币种合约
			'limitHeight'    => 10 //有效区块高度限制
		);
    }
}