<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\UserModel;
use App\Models\ReplyModel;
use App\Models\PraiseModel;

class CommentModel extends Model {

	public function __construct(){
        parent::__construct();
        $this->tablename   = 'wet_comment';
		$this->wet_reply   = 'wet_reply';
		$this->bloom	   = new BloomModel();
		$this->user	   	   = new UserModel();
		$this->reply	   = new ReplyModel();
		$this->praise	   = new PraiseModel();
    }

	public function txComment($hash, $opt=[])
	{//获取评论内容
        $sql="SELECT to_hash,
					sender_id,
					payload,
					utctime,
					commsum,
					love
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query-> getRow();
        if($row){
			$data['hash']    = $hash;
			$data['toHash']  = $row-> to_hash;
			$sender_id       = $row-> sender_id;
			$bloom           = $this->bloom-> txBloom($hash);
			if($bloom){
				$data['payload'] = htmlentities($row-> payload);
			}else{
				$data['payload'] = $hash;
			}
			$data['utcTime']		= (int) $row-> utctime;
			$data['commentNumber']  = (int) $row-> commsum;
			$data['praise']			= (int) $row-> love;
			if($opt['userLogin']){
				$data['isPraise']	= $this->praise-> isPraise($hash, $opt['userLogin']);
			}else{
				$data['isPraise']	= false;
			}
			$data['users']			= $this->user-> getUser($sender_id);
			if($opt['replyLimit']){
				$data['commentList'] = [];
				if((int) $opt['replyLimit']){
					$replyLimit = max(0, (int)$opt['replyLimit']);
					$limit = 'LIMIT '.$replyLimit;
				}else{
					$limit = 'LIMIT 0';
				}
				$replySql = "SELECT hash FROM wet_reply WHERE to_hash='$hash' ORDER BY uid DESC ".$limit;
				$query = $this-> db-> query($replySql);
				foreach ($query-> getResult() as $row){
					$hash  = $row -> hash;
					$bloom = $this->bloom-> txBloom($hash);
					if($bloom){
						$list[] = $this->reply-> txReply($hash);
					}
					$data['commentList'] = $list;
				}
			}
        }

    return $data;
    }

}