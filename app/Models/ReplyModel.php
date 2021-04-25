<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\PraiseModel;
use App\Models\DisposeModel;

class ReplyModel extends Model {

	public function __construct(){
        parent::__construct();
        $this->tablename    = 'wet_reply';
		$this->bloom	    = new BloomModel();
		$this->user	   	    = new UserModel();
		$this->praise	    = new PraiseModel();
		$this->DisposeModel	= new DisposeModel();
    }

	public function txReply($hash, $opt=[])
	{//获取回复内容
        $sql="SELECT hash,
					to_hash,
					reply_hash,
					reply_type,
					payload,
					sender_id,
					to_address,
					utctime,
					praise
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']		  = $hash;
			$data['toHash']		  = $row-> to_hash;
			$data['replyType']	  = $row-> reply_type;
			$data['replyHash']    = $row-> reply_hash;
			$data['payload']	  = $this->DisposeModel-> delete_xss($row-> payload);
			$sender_id			  = $row-> sender_id;
			$to_address			  = $row-> to_address;
			$data['senderId']	  = $sender_id;
			$data['toAddress']    = $to_address;
			$data['receiverName'] = $this->user-> getName($to_address);
			$data['utcTime']	  = (int) $row-> utctime;
			$data['praise']		  = (int) $row-> praise;
			if($opt['userLogin']){
				$data['isPraise'] = $this->praise-> isPraise($hash, $opt['userLogin']);
			} else {
				$data['isPraise'] = false;
			}
			$data['users']		  = $this->user-> getUser($sender_id);
        }

    return $data;
    }

}

