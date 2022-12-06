<?php 
namespace App\Models\Content;

use App\Models\{
	ComModel,
	ValidModel,
	RewardModel,
	DisposeModel,
	UserModel
};

class ContentPullModel
{//主贴Model

	private $tablename;

	public function __construct()
	{
		$this->UserModel	= new UserModel();
		$this->RewardModel	= new RewardModel();
		$this->tablename 	= "wet_content";
    }

	public function txContent($hash, $opt = [])
	{//获取主贴内容
		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload  = "payload";
		}

		$sql = "SELECT sender_id,
						$payload,
						media_list,
						utctime,
						comment_sum,
						praise,
						star_sum,
						read_sum,
						reward_sum,
						source,
						chain_id
				FROM $this->tablename 
				WHERE hash = '$hash' LIMIT 1";

        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
        if ($row) {
			$data['hash'] 			= $hash;
			$sender_id	  			= $row-> sender_id;
			$operation				= mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount				= $strCount ? $operation : $row->payload;
			$deleteXss				= DisposeModel::delete_xss($isStrCount);
			$data['payload']		= DisposeModel::sensitive($deleteXss);
			//$data['imgTx']			= $row->img_tx ? "https://api.wetrue.io/Image/toimg/".$hash : "";
			$data['mediaList']		= json_decode($row->media_list, true) ?? [];
			$data['utcTime']		= (int) $row-> utctime;
			$data['commentNumber']  = (int) $row-> comment_sum;
			$data['praise']			= (int) $row-> praise;
			$data['star']			= (int) $row-> star_sum;
			$data['read']			= (int) $row-> read_sum;
			$data['reward']			= $row-> reward_sum;
			if (isset($opt['rewardList'])) {
				$data['rewardList']	= $this->RewardModel-> rewardList($hash);
			}
			if (isset($opt['userLogin'])) {
				$data['isPraise']	= ValidModel::isPraise($hash, $opt['userLogin']);
				$data['isStar']		= ValidModel::isStar($hash, $opt['userLogin']);
				$data['isFocus']	= ValidModel::isFocus($sender_id, $opt['userLogin']);
			} else {
				$data['isPraise']	= false;
				$data['isStar']		= false;
				$data['isFocus']	= false;
			}
			$data['source']			= $row->source ? $row->source : "WeTrue";
			$data['chainId']		= $row->chain_id ? (int) $row->chain_id : 457;
			$data['users']			= $this->UserModel-> getUser($sender_id);
			if (isset($opt['read'])) {
				$upReadSql = "UPDATE $this->tablename SET read_sum = read_sum + 1 WHERE hash = '$hash'";
				ComModel::db()-> query($upReadSql);
			}
			
        }
    	return $data;
    }

	public function simpleContent($hash, $opt=[])
	{//获取简单主贴内容
		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload = "payload";
		}

		$sql = "SELECT sender_id,
						$payload,
						img_tx
				FROM $this->tablename 
				WHERE hash='$hash' LIMIT 1";

        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash'] = $hash;
			$sender_id  = $row-> sender_id;
			$operation  = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount = $strCount ? $operation : $row->payload;
			$deleteXss  = DisposeModel::delete_xss($isStrCount);
			$data['payload']   = DisposeModel::sensitive($deleteXss);
			$data['mediaList'] = json_decode($row->media_list, true) ?? [];
			$data['users']['nickname'] = $this->UserModel-> getName($sender_id);
			$data['users']['userAddress'] = $sender_id;	
        }
    	return $data;
    }

}

