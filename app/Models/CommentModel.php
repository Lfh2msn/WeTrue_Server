<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\UserModel;
use App\Models\ReplyModel;
use App\Models\ValidModel;
use App\Models\DisposeModel;

class CommentModel extends Model {
//评论Model

	public function __construct()
	{
        //parent::__construct();
		$this->db 			= \Config\Database::connect('default');
		$this->bloom	    = new BloomModel();
		$this->user	   	    = new UserModel();
		$this->reply	    = new ReplyModel();
		$this->ValidModel	= new ValidModel();
		$this->DisposeModel	= new DisposeModel();
		$this->wet_comment  = 'wet_comment';
		$this->wet_reply    = 'wet_reply';
    }

	public function txComment($hash, $opt=[])
	{//获取评论内容
        $sql="SELECT to_hash,
					sender_id,
					payload,
					utctime,
					comment_sum,
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
			$data['replyNumber'] = (int) $row-> comment_sum;
			$data['praise']		 = (int) $row-> praise;
			$data['isPraise']	 = $opt['userLogin'] ? $this->ValidModel-> isPraise($hash, $opt['userLogin']) : false;
			$data['users'] = $this->user-> getUser($sender_id);
			if ( (int)$opt['replyLimit'] ) {
				$data['commentList'] = [];
				$replyLimit = max(0, (int)$opt['replyLimit']);
				$limit    = 'LIMIT '.$replyLimit;
				$replySql = "SELECT hash FROM $this->wet_reply WHERE to_hash = '$hash' ORDER BY utctime DESC ".$limit;
				$query    = $this-> db-> query($replySql);
				foreach ($query-> getResult() as $row) {
					$hash  = $row -> hash;
					$txBloom = $this->bloom-> txBloom($hash);
					if (!$txBloom) {
						$opt['substr']	  = 140; //限制输出
						$list[] = $this->reply-> txReply($hash, $opt);
					}
					$data['commentList'] = $list;
				}
			}
        }

    return $data;
    }

}