<?php namespace App\Models\Config;

class AeChainPutConfig
{//AE上链收录 配置

	public static function amount()
	{//推荐因子
		return array(
			'topic'    => "1000000000000000",      //默认1e15，发帖消耗AE 1e15 = 1000000000000000 = 0.001ae
			'comment'  => "100000000000000",       //默认1e14，评论消耗AE
			'reply'    => "100000000000000",       //默认1e14，回复消耗AE
			'nickname' => "10000000000000000",     //默认1e16，昵称消耗AE
			'sex'      => "10000000000000000",     //默认1e16，性别消耗AE
			'star'     => "0",					 //默认0，收藏消耗AE
			'focus'    => "0",					 //默认0，关注消耗AE
			'drift'    => "100000000000000000",    //默认1e17，漂流瓶消耗AE
			'driftReply'   => "1000000000000000",      //默认1e15，漂流瓶回复消耗AE
			'driftSalvage' => "5000000000000000000",   //默认5e18，漂流瓶打捞消耗WTT
			'notesPay'     => "10000000000000000000",  //默认10e18，笔记消耗WTT
			'usableHoldAE' => true,  					 //用户发帖需持有最低AE开关
			'usableHoldAettos' => "1000000000000000000"   //默认1e18，用户发帖需持有最低AE数量
		);
    }

}