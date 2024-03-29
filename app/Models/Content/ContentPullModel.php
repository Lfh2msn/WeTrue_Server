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

	public static function txContent($hash, $opt = [])
	{//获取主贴内容
		if ( (isset($opt['substr'])) ) {
			$sqlPayload  = "substring(payload for '$opt[substr]') as payload";
		} else {
			$sqlPayload  = "payload";
		}

		$sql = "SELECT sender_id,
						$sqlPayload,
						media_list,
						utctime,
						comment_sum,
						praise,
						star_sum,
						read_sum,
						reward_sum,
						source,
						chain_id
				FROM wet_content 
				WHERE hash = '$hash' LIMIT 1";

        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
        if ($row) {
			$data['hash'] 			= $hash;
			$sender_id	  			= $row-> sender_id;
			if(isset($opt['substr'])){
				$payload = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			} else {
				$payload = $row->payload;
			}
			$deleteXss  			= DisposeModel::delete_xss($payload);
			$data['payload']   	    = DisposeModel::sensitive($deleteXss);
			//$data['imgTx']		= $row->img_tx ? "https://api.wetrue.io/Image/toimg/".$hash : "";
			$data['mediaList']		= json_decode($row->media_list, true) ?? [];
			$data['utcTime']		= (int) $row-> utctime;
			$data['commentNumber']  = (int) $row-> comment_sum;
			$data['praise']			= (int) $row-> praise;
			$data['star']			= (int) $row-> star_sum;
			$data['read']			= (int) $row-> read_sum;
			$data['reward']			= $row-> reward_sum;
			if (isset($opt['rewardList'])) {
				$data['rewardList']	= RewardModel::rewardList($hash);
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
			$data['users']			= UserModel::getUser($sender_id);
			if (isset($opt['read'])) {
				$upReadSql = "UPDATE wet_content SET read_sum = read_sum + 1 WHERE hash = '$hash'";
				ComModel::db()-> query($upReadSql);
			}
			
        }
    	return $data;
    }

	public static function simpleContent($hash, $opt=[])
	{//获取简单主贴内容
		if (isset($opt['substr'])) {
			$sqlPayload  = "substring(payload for '$opt[substr]') as payload";
		} else {
			$sqlPayload = "payload";
		}

		$sql = "SELECT sender_id,
						$sqlPayload,
						img_tx
				FROM wet_content 
				WHERE hash='$hash' LIMIT 1";

        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['hash'] = $hash;
			$sender_id  = $row-> sender_id;
			if(isset($opt['substr'])){
				$payload = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			} else {
				$payload = $row->payload;
			}
			$deleteXss  = DisposeModel::delete_xss($payload);
			$data['payload']   = DisposeModel::sensitive($deleteXss);
			$data['mediaList'] = isset($row->media_list) ? json_decode($row->media_list, true) : [];
			$data['users']['nickname'] = UserModel::getName($sender_id);
			$data['users']['userAddress'] = $sender_id;	
        }
    	return $data;
    }

}

