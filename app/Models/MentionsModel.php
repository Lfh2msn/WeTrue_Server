<?php 
namespace App\Models;

use App\Models\{
	MsgModel,
	AensModel
};
use App\Models\Get\GetAeChainModel;

class MentionsModel
{//@模块 Model

	public function __construct(){
		$this->MsgModel   = new MsgModel();
    }

	public function isMentions($content)
	{//内容搜索“@”
		$topicTag = preg_match_all("/@[\p{L}\d]+.chain/u", $content, $keywords);
		return $topicTag ? $keywords[0] : false;
		
	}

	public function getAddressByAensPoint($aens)
	{//AENS获取AE地址
		$aens    = str_replace("@", "" ,$aens);
		$address = GetAeChainModel::addressByNamePoint($aens);
		AensModel::insertUserAens($address, $aens);
		return $address;
	}

	public function messageMentions($data=[])
	{/*@ 消息通知入库
		$data = [
			'type'		=> topic\comment\reply,
			'hash'		=> hash,
			'toHash'	=> toHash,
			'content'   => 内容,
			'sender_id' => 发送人,
			'utctime'   => 时间戳
		]; */

		$names = $this->isMentions($data['content']);
		$names = array_unique($names);
		$names = array_values($names);
		if ($names) {
			foreach ($names as $name) {
				$address = $this->getAddressByAensPoint($name);
				if ($address) {
					$msgData = [
						'type'	   	   => 'mentions',
						'hash' 		   => isset($data['hash']) ? $data['hash'] : '',
						'toHash' 	   => $data['toHash'],
						'sender_id'	   => $data['sender_id'],
						'recipient_id' => $address,
						'utctime' 	   => $data['utctime']
					];
					$this->MsgModel-> addMsg($msgData);
				}
			}
		}
	}


}

