<?php namespace App\Models\Config;

class ContentRecConfig {
//内容推荐 配置

	public function factor()
	{//推荐因子
		return array(
			'hotDay'  => 4,  //热点推荐天数
			'praise'  => 2,  //点赞因子,越大权重越大
			'comment' => 20,  //评论人数因子,越大权重越大
			'star'	  => 5,  //收藏因子,越大权重越大
			'read'	  => 1,  //阅读量因子,越大权重越大
			'time'	  => 5,  //时间因子,越大权重越小
			'reward'  => "400000000000"  //4e11,打赏因子,越大权重越小
		);
    }

}