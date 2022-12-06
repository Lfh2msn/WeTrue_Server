<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ValidModel,
	DisposeModel
};

class ReplyModel
{//回复Model

	public function __construct(){
		$this->UserModel	= new UserModel();
		$this->tablename    = "wet_reply";
    }

	public function txReply($hash, $opt=[])
	{//获取回复内容
		if ((int)$opt['substr']) {
			$payload = "substring(payload for '$opt[substr]') as payload";
		} else {
			$payload = "payload";
		}

		$sql = "SELECT hash,
							to_hash,
							reply_hash,
							reply_type,
							$payload,
							sender_id,
							to_address,
							utctime,
							praise,
							chain_id
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";

        $query = ComModel::db()->query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$sender_id			  = $row-> sender_id;
			$to_address			  = $row-> to_address;
			$data['hash']		  = $hash;
			$data['toHash']		  = $row-> to_hash;
			$data['to_hash']	  = $row-> to_hash; //即将废弃
			$data['replyType']	  = $row-> reply_type;
			$data['replyHash']    = $row-> reply_hash;
			$data['payload']	  = DisposeModel::delete_xss($row-> payload);
			$data['toAddress']    = $to_address;
			$data['receiverName'] = $to_address ? $this->UserModel-> getName($to_address) : '';
			$data['receiverIsAuth'] = $to_address ? ValidModel::isAuthUser($to_address) : false;
			$data['utcTime']	  = (int) $row-> utctime;
			$data['praise']		  = (int) $row-> praise;
			$data['isPraise']	  = $opt['userLogin'] ? ValidModel::isPraise($hash, $opt['userLogin']) : false;
			$data['chainId']	  = $row->chain_id ? (int) $row->chain_id : 457;
			$data['users']		  = $this->UserModel-> getUser($sender_id);
        }

    return $data;
    }

}

