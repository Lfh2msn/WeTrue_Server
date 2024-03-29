<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	CommentModel,
	ReplyModel,
	RewardModel,
	ValidModel,
	DisposeModel
};
use App\Models\Content\{
	ContentPullModel,
	SuperheroContentModel
};
use App\Models\Wecom\{
	SendModel,
	CorpUserModel
};
use App\Models\Config\WecomConfig;

class MsgModel
{//消息Model

	public function __construct(){
		$this->SendModel    = new SendModel();
		$this->WecomConfig  = new WecomConfig();
		$this->CorpUserModel    = new CorpUserModel();
    }

    public function getMsgList($page, $size, $offset)
	{//获取消息列表
		$page   = max(1, (int)$page);
		$size   = max(1, (int)$size);
		$offset = max(0, (int)$offset);
		$akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		if (!$isAkToken) return DisposeModel::wetJsonRt(401,'error_address');

		$countSql = "SELECT count(hash) FROM wet_message WHERE recipient_id = '$akToken'";
		$limitSql = "SELECT hash, 
						to_hash, 
						type,
						state, 
						utctime 
					FROM wet_message 
						WHERE recipient_id = '$akToken' 
						ORDER BY utctime DESC, state DESC 
					LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		$returnData = $this->cycle($page, $size, $countSql, $limitSql);

		$upHashSql = "SELECT hash FROM wet_message 
						WHERE recipient_id = '$akToken'
						AND state = 1
						AND type <> 'reward' 
					LIMIT $size OFFSET ".(($page-1) * $size + $offset);
		$upStateSql = "UPDATE wet_message 
						SET state = CASE state
							WHEN 1 THEN 0
						END 
						WHERE hash IN ($upHashSql)";
		ComModel::db()-> query($upStateSql);
		return $returnData;
	}

	private function cycle($page, $size, $countSql, $limitSql)
	{//列表循环
		$data = $this->pages($page, $size, $countSql);
		$query = ComModel::db()-> query($limitSql);
		$getResult = $query-> getResult();
		$data['data'] = [];
		if ($getResult) {
			foreach ($getResult as $row) {
				$hash    = $row->hash;
				$to_hash = $row->to_hash;
				$type	 = $row->type;
				$state	 = $row->state ? true : false;
				$utctime = (int) $row->utctime;
				$opt['substr'] = 45; //限制Payload长度
				$opt = ['imgTx'=>true];
				if ($type  == 'comment' || ($type == 'mentions' && !$hash) ) {
					$isData['state']   = $state;
					$isData['type']    = $type;
					$isData['utctime'] = $utctime;
					$isData['topic']   = ContentPullModel::simpleContent($to_hash, $opt=[]);
					$isShTipid = DisposeModel::checkSuperheroTipid($to_hash);
					if ($isShTipid) $isData['topic'] = SuperheroContentModel::simpleContent($to_hash, $opt=[]);
					$comment = CommentModel::simpleComment($hash, $opt);
					$isData['comment'] = $comment ? $comment : [];
					$isData['reply']   = [];
					$isData['reward']  = [];
					if ($isData['topic'] || $isData['comment']) {
						$detaila[] = $isData;
					}
				}

				if ( $type  == 'reply' || ($type == 'mentions' && $hash) ) {
					$isData['state']   = $state;
					$isData['type']    = $type;
					$isData['utctime'] = $utctime;
					$commentPayload    = CommentModel::simpleComment($to_hash, $opt);
					$topicHash		   = $commentPayload['to_hash'];
					$isData['topic']   = ContentPullModel::simpleContent($topicHash, $opt=[]);
					$isData['comment'] = $commentPayload;
					$isData['reply']   = ReplyModel::txReply($hash);
					$isData['reward']  = [];
					if($isData['topic'] && $isData['comment'] && $isData['reply']) {
						$detaila[] = $isData;
					}
				}

				if ($type  == 'reward') {
					$isData['state']   = $state;
					$isData['type']    = $type;
					$isData['utctime'] = $utctime;
					$isData['topic']   = ContentPullModel::simpleContent($to_hash, $opt=[]);
					$isData['reward']  = RewardModel::simpleReward($hash);
					$isData['comment'] = [];
					$isData['reply']   = [];
					if($isData['topic'] && $isData['reward']) {
						$detaila[] = $isData;
					}
				}
				$data['data'] = $detaila;
			}
		}
		return DisposeModel::wetJsonRt(200,'success',$data);
	}

	public function addMsg($data=[])
	{/*添加消息
		[	
			type 	 	 => comment\reply\shTipid\mentions, 
			hash 		 => hash,
			to_hash 	 => hash,
			sender_id 	 => address, 发送人地址 
			recipient_id => address, 
			state 		 => 0\1, 状态[默认 1]
			utctime 	 => timestamp ,时间戳
		] */

		if (!$data && !$data['hash']) {
            return DisposeModel::wetRt(406, '数据不能为空');
        } elseif (!$data['type']) {
            return DisposeModel::wetRt(406, 'type不能为空');
        } elseif (!$data['sender_id']) {
            return DisposeModel::wetRt(406, 'sender_id不能为空');
        } elseif (!$data['recipient_id']) {
            return DisposeModel::wetRt(406, 'recipient_id不能为空');
        } elseif ($data['sender_id'] == $data['recipient_id']) {
            return DisposeModel::wetRt(406, '不需要对自己提醒');
        }

		$insertData = [
			'hash' 		   => isset($data['hash']) 		   ? $data['hash'] 		   : '',
			'to_hash' 	   => isset($data['toHash']) 	   ? $data['toHash'] 	   : '',
			'type'	   	   => isset($data['type'])   	   ? $data['type']   	   : '',
			'sender_id'	   => isset($data['sender_id'])    ? $data['sender_id']	   : '',
			'recipient_id' => isset($data['recipient_id']) ? $data['recipient_id'] : '',
			'state' 	   => isset($data['state']) 	   ? (int) $data['state']  : 1,
			'utctime' 	   => isset($data['utctime']) 	   ? $data['utctime'] 	   : (time() * 1000),
		];
		ComModel::db()->table('wet_message')->insert($insertData);
		$isWecomAddress = ValidModel::isWecomAddress($data['recipient_id']);
		if ($isWecomAddress) $this->pushWecom($data);
	}

	public function toHashSendID($hash, $opt=[])
	{//hash获取被评论人ID
		$whereHash = 'hash';
		if ($opt['type'] == 'comment') {
			$this->tablename = "wet_content";
		} elseif ($opt['type'] == 'reply') {
			$this->tablename = "wet_comment";
		} elseif ($opt['type'] == 'shTipid') {
			$this->tablename = "wet_content_sh";
			$whereHash = 'tip_id';
		} else {
			return;
		}

		$sql   = "SELECT sender_id FROM $this->tablename WHERE $whereHash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		$send  = $row->sender_id;
		return $send;
	}

	public function pushWecom($data)
	{//推送到企业微信
		$toHash 	= $data['toHash'];
		$reqAddress = $data['recipient_id'];
		$type   	= $data['type'];

		if ($type == 'comment') {
			$toHash = $data['toHash'];
			$type 	 = "评论";
		} elseif ($type == 'reply') {
			$opt['substr'] = 45; //限制Payload长度
			$opt = ['imgTx'=>true];
			$comHash = CommentModel::simpleComment($toHash, $opt);
			$toHash = $comHash['toHash'];
			$type    = "回复";
		} else {
			return;
		}

		$url 		 = "https://wetrue.cc/#/pages/index/detail?hash={$toHash}";
		$description = "您收到一条来自WeTrue{$type},点击查看详情";
		$weConfig    = $this->WecomConfig-> config();
		$wetrueKey   = $weConfig['WETRUE_KEY_1'];
		$touser		 = $this->CorpUserModel-> getUserId($reqAddress);
		if ($touser){
			$payload = [
				'msgtype' 	  => 'textcard',
				'title'   	  => '收到一条 WeTrue 消息',
				'description' => $description,
				'url' 		  => $url,
				'btntxt'	  => '详情'
			];
			$this->SendModel-> sendToWecom($payload, $wetrueKey, $touser);
		}
	}

	public function getStateSize()
	{//获取未读消息数
		$akToken   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAkToken = DisposeModel::checkAddress($akToken);
		if (!$isAkToken) return 'error_address';
		$sql = "SELECT count(hash) FROM wet_message WHERE recipient_id = '$akToken' AND state = '1' AND type <> 'reward' ";
		$query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
		return $row ? (int)$row->count : 0;
	}

	private function pages($page, $size, $sql)
	{
		$query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;//总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int) ceil($count / $size),  //总页数
			'totalSize'	=> (int) $count  //总数量
		];
		return $data;
	}

}

