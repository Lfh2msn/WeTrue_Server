<?php namespace App\Models\Config;

use App\Models\Config\AeTokenConfig;

class OpenVipConfig {
//开通vip配置

    public function config()
	{//配置
		$tokenName  = 'WTT';
		$contractId = (NEW AeTokenConfig())-> getContractId($tokenName);
		
		return array(
			'openVip'     	   => true, //vip可开通状态
			'openVipAddress'   => 'ak_2afGvLkUTwdCixNLxKVbpmtGqDMSGVnU2orSTChNqKRcYk7xvV', //vip收款地址
			'openVipAmount'    => "680000000000000000000", //开通VIP金额,680WTT = 680000000000000000000
			'openTokenAddress' => $contractId //开通币种合约
		);
    }
}