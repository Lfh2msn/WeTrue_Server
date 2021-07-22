<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\ValidModel;
use App\Models\ContentModel;
use App\Models\CommentModel;
use App\Models\ReplyModel;


class MsgModel extends ComModel
{//消息Model

	public function __construct(){
        parent::__construct();
		$this->BloomModel   = new BloomModel();
		$this->ContentModel = new ContentModel();
		$this->CommentModel = new CommentModel();
		$this->ReplyModel 	= new ReplyModel();
		$this->ValidModel	= new ValidModel();
		$this->DisposeModel	= new DisposeModel();
		$this->wet_message  = "wet_message";
    }

    public function getMsgList($page, $size)
	{//获取消息列表

		$page = max(1, (int)$page);
		$size = max(1, (int)$size);
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if (!$isAkToken) return $this->DisposeModel-> wetJsonRt(401,'error_address');

		$countSql = "SELECT count(hash) FROM $this->wet_message WHERE recipient_id = '$akToken'";
		$limitSql = "SELECT 
						hash, 
						to_hash, 
						type, 
						sender_id, 
						recipient_id, 
						state, 
						utctime 
					FROM $this->wet_message 
					WHERE recipient_id = '$akToken'
					ORDER BY state DESC, utctime DESC 
					LIMIT $size OFFSET ".($page-1) * $size;
		$data = $this->cycle($page, $size, $countSql, $limitSql);

		$upHashSql = "SELECT hash FROM $this->wet_message 
						WHERE recipient_id = '$akToken'
						AND state = 1
					LIMIT $size OFFSET ".($page-1) * $size;
		$upStateSql = "UPDATE $this->wet_message 
						SET state = CASE state
							WHEN 1 THEN 0
						END 
						WHERE hash IN ($upHashSql)";
		$this->db-> query($upStateSql);
		return $data;
	}

	private function cycle($page, $size, $countSql, $limitSql)
	{//列表循环
		$data = $this->pages($page, $size, $countSql);
		$data['data'] = [];
		$query = $this->db-> query($limitSql);
		$getResult = $query-> getResult();
		
		if($getResult){
			foreach ($getResult as $row) {
				$hash    = $row->hash;
				$toHash  = $row->to_hash;
				$type	 = $row->type;
				$state	 = $row->state ? true : false ;
				$txBloom = $this->BloomModel-> txBloom($hash);
				if (!$txBloom) {
					if ($type  == 'comment') {
						$opt['substr']     = 45; //限制Payload长度
						$isData['state']   = $state;
						$isData['type']    = $type;
						$isData['topic']   = $this->ContentModel-> simpleContent($toHash, $opt=[]);
						$isData['comment'] = $this->CommentModel-> simpleComment($hash);
						if($isData['topic'] && $isData['comment']) {
							$detaila[] = $isData;
						}
					}
	
					if ($type  == 'reply') {
						$opt['substr']     = 45; //限制Payload长度
						$isData['type']    = $type;
						$isData['topic']   = $this->CommentModel-> simpleComment($toHash, $opt=[]);
						$isData['comment'] = $this->ReplyModel->   txReply($hash);
						if($isData['topic'] && $isData['comment']) {
							$detaila[] = $isData;
						}
					}
				}
				$data['data'] = $detaila;
			}
		}
		$data = $this->DisposeModel-> wetJsonRt(200,'success',$data);
		return $data;
	}

	public function addMsg($data=[])
	{/*添加消息
		[
			hash => hash,
			to_hash => hash,
			type 	 	 => comment\reply, 
			sender_id 	 => address, 发送人地址 
			recipient_id => address, 
			state 		 => 0\1, 状态[默认 1]
			utctime 	 => timestamp ,时间戳
		]*/

		if (!$data && !$data['hash']) {
            return $this->DisposeModel-> wetRt(406, '数据不能为空');
        } elseif (!$data['to_hash']) {
            return $this->DisposeModel-> wetRt(406, 'to_hash不能为空');
        } elseif (!$data['type']) {
            return $this->DisposeModel-> wetRt(406, 'type不能为空');
        } elseif (!$data['sender_id']) {
            return $this->DisposeModel-> wetRt(406, 'sender_id不能为空');
        } elseif (!$data['recipient_id']) {
            return $this->DisposeModel-> wetRt(406, 'recipient_id不能为空');
        } elseif ($data['sender_id'] == $data['recipient_id']) {
            return $this->DisposeModel-> wetRt(406, '不需要对自己提醒');
        }

		$insertData = [
			'hash' 		   => isset($data['hash']) 		   ? $data['hash'] 		   : '',
			'to_hash' 	   => isset($data['to_hash']) 	   ? $data['to_hash'] 	   : '',
			'type'	   	   => isset($data['type'])   	   ? $data['type']   	   : '',
			'sender_id'	   => isset($data['sender_id'])    ? $data['sender_id']	   : '',
			'recipient_id' => isset($data['recipient_id']) ? $data['recipient_id'] : '',
			'state' 	   => isset($data['state']) 	   ? (int) $data['state']  : 1,
			'utctime' 	   => isset($data['utctime']) 	   ? $data['utctime'] 	   : time(),
		];
		$this->db->table($this->wet_message)->insert($insertData);
	}

	public function toHashSendID($hash, $opt=[])
	{//hash获取被评论人ID
		if ($opt['type'] == 'comment') {
			$this->tablename = "wet_content";
		} elseif ($opt['type'] == 'reply') {
			$this->tablename = "wet_comment";
		} else {
			return;
		}
		
		$sql   = "SELECT sender_id FROM $this->tablename WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		$send  = $row->sender_id;
		return $send;
	}

	public function getStateSize()
	{//获取未读消息数
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if (!$isAkToken) return 'error_address';
		$sql = "SELECT count(hash) FROM $this->wet_message WHERE recipient_id = '$akToken' AND state = '1'";
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
		return $row ? (int)$row->count : 0;
	}

	private function pages($page, $size, $sql)
	{
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
        $count = $row->count;//总数量
		$data  = [
			'page'		=> $page,  //当前页
			'size'		=> $size,  //每页数量
			'totalPage'	=> (int)ceil($count/$size),  //总页数
			'totalSize'	=> (int)$count  //总数量
		];
		return $data;
	}

}
