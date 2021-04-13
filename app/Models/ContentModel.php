<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BloomModel;
use App\Models\UserModel;
use App\Models\PraiseModel;
use App\Models\StarModel;
use App\Models\FocusModel;

class ContentModel extends Model {

	public function __construct(){
        parent::__construct();
        $this->tablename = 'wet_content';
		$this->bloom	 = new BloomModel();
		$this->user	   	 = new UserModel();
		$this->praise	 = new PraiseModel();
		$this->star		 = new StarModel();
		$this->focus	 = new FocusModel();
    }

	public function txContent($hash, $opt=[])
	{//获取主贴内容
		if(!$opt['substr']){
			$sql = "SELECT sender_id,
							payload,
							img_tx,
							utctime,
							comment_sum,
							praise,
							star
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";
		}else{
			$sql = "SELECT sender_id,
							substring(payload for $opt[substr]) as payload,
							img_tx,
							utctime,
							comment_sum,
							praise,
							star
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";
		}
        
        $query = $this-> db-> query($sql);
		$row   = $query-> getRow();
        if($row){
			$data['hash'] = $hash;
			$sender_id	  = $row-> sender_id;
			$bloom		  = $this->bloom-> txBloom($hash);
			if($bloom){
				$data['payload']	= stripslashes($row-> payload);
				$data['imgTx']		= stripslashes($row-> img_tx);
			}else{
				$data['payload']	= $hash;
				$data['imgTx']		= "";
			}

			$data['utcTime']		= (int) $row-> utctime;
			$data['praise']			= (int) $row-> praise;
			$data['star']			= (int) $row-> star;
			if($opt['userLogin']){
				$data['isPraise']	= $this->praise-> isPraise($hash, $opt['userLogin']);
				$data['isStar']		= $this->star-> isStar($hash, $opt['userLogin']);
				$data['isFocus']	= $this->focus-> isFocus($sender_id, $opt['userLogin']);
			}else{
				$data['isPraise']	= false;
				$data['isStar']		= false;
				$data['isFocus']	= false;
			}
			
			$data['commentNumber']  = (int) $row-> comment_sum;
			$data['users']			= $this->user-> getUser($sender_id);
        }

    return $data;
    }

}

