<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\ValidModel;

class ContentModel extends ComModel
{//主贴Model

	public function __construct()
	{
        parent::__construct();
        $this->tablename 	= 'wet_content';
		$this->DisposeModel	= new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->UserModel	= new UserModel();
    }

	public function txContent($hash, $opt=[])
	{//获取主贴内容
		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload  = "payload";
		}

		$sql = "SELECT sender_id,
							$payload,
							img_tx,
							utctime,
							comment_sum,
							praise,
							star_sum,
							read_sum,
							reward_sum,
							source
				FROM $this->tablename WHERE hash='$hash' LIMIT 1";

        $query = $this-> db-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash'] = $hash;
			$sender_id	  = $row-> sender_id;
			$operation				= mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount				= $strCount ? $operation : $row->payload;
			$deleteXss				= $this->DisposeModel-> delete_xss($isStrCount);
			$data['payload']		= $this->DisposeModel-> sensitive($deleteXss);
			$data['imgTx']			= $row->img_tx ? "https://api.wetrue.io/Image/toimg/".$hash : "";
			$data['utcTime']		= (int) $row-> utctime;
			$data['commentNumber']  = (int) $row-> comment_sum;
			$data['praise']			= (int) $row-> praise;
			$data['star']			= (int) $row-> star_sum;
			$data['read']			= (int) $row-> read_sum;
			$data['reward']			= (int) $row-> reward_sum;
			if ($opt['userLogin']) {
				$data['isPraise']	= $this->ValidModel-> isPraise($hash, $opt['userLogin']);
				$data['isStar']		= $this->ValidModel-> isStar($hash, $opt['userLogin']);
				$data['isFocus']	= $this->ValidModel-> isFocus($sender_id, $opt['userLogin']);
			} else {
				$data['isPraise']	= false;
				$data['isStar']		= false;
				$data['isFocus']	= false;
			}
			$data['source']			= $row->source ? $row->source : "WeTrue";
			$data['users']			= $this->UserModel-> getUser($sender_id);
			if ($opt['read']) {
				$upReadSql = "UPDATE $this->tablename SET read_sum = read_sum + 1 WHERE hash = '$hash'";
				$this->db-> query($upReadSql);
			}
			
        }

    return $data;
    }

}

