<?php namespace App\Models\Config;

class ActiveConfig {
//活跃度配置

    public function config()
	{//配置
		return array(
			'topicActive'        => 5,  //发帖 +活跃度
			'commentActive'      => 2,  //评论 +活跃度
			'replyActive'        => 2,  //回复 +活跃度
			'praiseActive'       => 1,  //点赞 +活跃度
			'nicknameActive'     => 10,  //昵称 +活跃度
			'sexActive'     	 => 10,  //性别 +活跃度
			'avatarActive'       => 10,  //头像 +活跃度
			'driftActive'     	 => 3,  //漂流瓶 +活跃度
			'driftReplyActive'   => 1,  //漂流瓶回复 +活跃度
			'complainActive'     => 30  //举报 -活跃度
		);
    }
}