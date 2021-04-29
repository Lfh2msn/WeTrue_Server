<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\UserModel;
use App\Models\ReplyModel;
use App\Models\PraiseModel;
use App\Models\DisposeModel;

class CommentModel extends Model {

	public function __construct(){
        parent::__construct();
        $this->wet_comment  = 'wet_comment';
		$this->wet_reply    = 'wet_reply';
		$this->bloom	    = new BloomModel();
		$this->user	   	    = new UserModel();
		$this->reply	    = new ReplyModel();
		$this->praise	    = new PraiseModel();
		$this->DisposeModel	= new DisposeModel();
    }

	public function txComment($hash, $opt=[])
	{//获取评论内容
        $sql="SELECT to_hash,
					sender_id,
					payload,
					utctime,
					comment_num,
					praise
				FROM $this->wet_comment WHERE hash='$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash']    	 = $hash;
			$data['toHash']  	 = $row-> to_hash;
			$sender_id       	 = $row-> sender_id;
			$data['payload']	 = $this->DisposeModel-> delete_xss($row-> payload);
			$data['utcTime']	 = (int) $row-> utctime;
			$data['replyNumber'] = (int) $row-> comment_num;
			$data['praise']		 = (int) $row-> praise;
			$data['isPraise']	 = $opt['userLogin'] ? $this->praise-> isPraise($hash, $opt['userLogin']) : false;
			$data['users'] = $this->user-> getUser($sender_id);
			if ( (int)$opt['replyLimit'] ) {
				$data['commentList'] = [];
				$replyLimit = max(0, (int)$opt['replyLimit']);
				$limit    = 'LIMIT '.$replyLimit;
				$replySql = "SELECT hash FROM $this->wet_reply WHERE to_hash = '$hash' ORDER BY utctime DESC ".$limit;
				$query    = $this-> db-> query($replySql);
				foreach ($query-> getResult() as $row) {
					$hash  = $row -> hash;
					$bloom = $this->bloom-> txBloom($hash);
					if ($bloom) {
						$list[] = $this->reply-> txReply($hash);
					}
					$data['commentList'] = $list;
				}
			}
        }

    return $data;
    }

}