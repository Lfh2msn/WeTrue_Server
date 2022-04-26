<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	UserModel,
	ReplyModel,
	ValidModel,
	DisposeModel
};

class CommentModel extends Model {
//评论Model

	public function __construct()
	{
        //parent::__construct();
		$this->db 			= \Config\Database::connect('default');
		$this->UserModel	= new UserModel();
		$this->ReplyModel	= new ReplyModel();
		$this->ValidModel	= new ValidModel();
		$this->DisposeModel	= new DisposeModel();
		$this->wet_comment  = "wet_comment";
		$this->wet_reply    = "wet_reply";
    }

	public function txComment($hash, $opt=[])
	{//获取评论内容
        $sql = "SELECT
					to_hash,
					sender_id,
					payload,
					utctime,
					comment_sum,
					praise,
					chain_id
				FROM $this->wet_comment 
				WHERE hash='$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']    	 = $hash;
			$data['toHash']  	 = $row-> to_hash;
			$sender_id       	 = $row-> sender_id;
			$data['payload']	 = $this->DisposeModel-> delete_xss($row-> payload);
			$data['utcTime']	 = (int) $row-> utctime;
			$data['replyNumber'] = (int) $row-> comment_sum;
			$data['praise']		 = (int) $row-> praise;
			$data['isPraise']	 = $opt['userLogin'] ? $this->ValidModel-> isPraise($hash, $opt['userLogin']) : false;
			$data['chainId']	 = $row->chain_id ? (int) $row->chain_id : 457;
			$data['users'] = $this->UserModel-> getUser($sender_id);
			if ( (int)$opt['replyLimit'] ) {
				$data['commentList'] = [];
				$replyLimit = max(0, (int)$opt['replyLimit']);
				$limit    = 'LIMIT '.$replyLimit;
				$replySql = "SELECT hash FROM $this->wet_reply WHERE to_hash = '$hash' ORDER BY utctime DESC ".$limit;
				$query    = $this-> db-> query($replySql);
				foreach ($query-> getResult() as $row) {
					$hash  = $row -> hash;
					$isBloomHash = $this->ValidModel-> isBloomHash($hash);
					if (!$isBloomHash) {
						$opt['substr'] = 140; //限制输出
						$list[] = $this->ReplyModel-> txReply($hash, $opt);
					}
					$data['commentList'] = $list;
				}
			}
        }

    return $data;
    }

	public function simpleComment($hash, $opt=[])
	{//获取简单评论内容
		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload  = "payload";
		}

		$sql = "SELECT
					to_hash,
					sender_id,
					$payload
				FROM $this->wet_comment 
				WHERE hash='$hash' LIMIT 1";
        $query = $this-> db-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']    = $hash;
			$data['toHash']  = $row-> to_hash;
			$sender_id	  	 = $row-> sender_id;
			$operation		 = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount		 = $strCount ? $operation : $row->payload;
			$deleteXss		 = $this->DisposeModel-> delete_xss($isStrCount);
			$data['payload'] = $this->DisposeModel-> sensitive($deleteXss);
			if (!$opt['imgTx']) {
				$data['imgTx'] = $this->UserModel-> getPortraitUrl($sender_id);
			}
			$data['users']   = $this->UserModel-> getUser($sender_id);
        }
    	return $data;
    }

}