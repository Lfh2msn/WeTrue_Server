<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ReplyModel,
	ValidModel,
	DisposeModel
};

class CommentModel
{//评论Model

	public static function txComment($hash, $opt=[])
	{//获取评论内容
        $sql = "SELECT
					to_hash,
					sender_id,
					payload,
					utctime,
					comment_sum,
					praise,
					chain_id
				FROM wet_comment 
				WHERE hash='$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']    	 = $hash;
			$data['toHash']      = $row-> to_hash;
			$data['to_hash']  	 = $row-> to_hash; //即将废弃
			$sender_id       	 = $row-> sender_id;
			$data['payload']	 = DisposeModel::delete_xss($row-> payload);
			$data['utcTime']	 = (int) $row-> utctime;
			$data['replyNumber'] = (int) $row-> comment_sum;
			$data['praise']		 = (int) $row-> praise;
			$data['isPraise']	 = $opt['userLogin'] ? ValidModel::isPraise($hash, $opt['userLogin']) : false;
			$data['chainId']	 = $row->chain_id ? (int) $row->chain_id : 457;
			$data['users'] = UserModel::getUser($sender_id);
			if ( (int)$opt['replyLimit'] ) {
				$data['commentList'] = [];
				$replyLimit = max(0, (int)$opt['replyLimit']);
				$limit    = 'LIMIT '.$replyLimit;
				$replySql = "SELECT hash FROM wet_reply WHERE to_hash = '$hash' ORDER BY utctime DESC ".$limit;
				$query    = ComModel::db()-> query($replySql);
				foreach ($query-> getResult() as $row) {
					$hash  = $row -> hash;
					$isBloomHash = ValidModel::isBloomHash($hash);
					if (!$isBloomHash) {
						$opt['substr'] = 140; //限制输出
						$list[] = ReplyModel::txReply($hash, $opt);
					}
					$data['commentList'] = $list;
				}
			}
        }

    return $data;
    }

	public static function simpleComment($hash, $opt=[])
	{//获取简单评论内容
		if ( (isset($opt['substr'])) ) {
			$sqlPayload  = "substring(payload for '$opt[substr]') as payload";
		} else {
			$sqlPayload  = "payload";
		}

		$sql = "SELECT
					to_hash,
					sender_id,
					$sqlPayload
				FROM wet_comment 
				WHERE hash='$hash' LIMIT 1";
        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']    = $hash;
			$data['toHash']  = $row-> to_hash; //即将废弃
			$data['to_hash'] = $row-> to_hash;
			$sender_id	  	 = $row-> sender_id;
			if(isset($opt['substr'])){
				$payload = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			} else {
				$payload = $row->payload;
			}
			$deleteXss  	 = DisposeModel::delete_xss($payload);
			$data['payload'] = DisposeModel::sensitive($deleteXss);
			$data['users']   = UserModel::getUser($sender_id);
        }
    	return $data;
    }

}