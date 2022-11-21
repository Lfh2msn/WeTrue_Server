<?php 
namespace App\Models;

use App\Models\{
	MsgModel,
	ValidModel,
	AensModel
};
use App\Models\Get\GetAeChainModel;

class MentionsModel extends ComModel
{//@模块 Model

	public function __construct(){
        parent::__construct();
		$this->GetAeChainModel = new GetAeChainModel();
		$this->MsgModel   = new MsgModel();
		$this->ValidModel = new ValidModel();
		$this->AensModel  = new AensModel();
    }

	public function isMentions($content)
	{//内容搜索“@”
		$topicTag = preg_match_all("/@[\p{L}\d]+.chain/u", $content, $keywords);
		return $topicTag ? $keywords[0] : false;
		
	}

	public function getAddressByAensPoint($aens)
	{//AENS获取AE地址
		$aens    = str_replace("@", "" ,$aens);
		$address = $this->GetAeChainModel->addressByNamePoint($aens);
		$this->AensModel-> insertUserAens($address, $aens);
		return $address;
	}

	public function messageMentions($data=[])
	{/*@ 消息通知入库
		$data = [
			'type'		=> topic\comment\reply,
			'hash'		=> hash,
			'to_hash'	=> to_hash,
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
						'to_hash' 	   => $data['to_hash'],
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

