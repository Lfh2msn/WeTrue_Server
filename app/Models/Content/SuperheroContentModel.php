<?php 
namespace App\Models\Content;

use App\Models\{
	ComModel,
	ValidModel,
	RewardModel,
	DisposeModel,
	UserModel,
	ConfigModel
};

class SuperheroContentModel
{//主贴Model

	public static function txContent($tip_id, $opt = [])
	{//获取主贴内容
		$bsConfig = ConfigModel::backendConfig();
		$shApiUrl = $bsConfig['superheroApiUrl'];
		
		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload  = "payload";
		}

		$sql = "SELECT sender_id,
						contract_id,
						source,
						type,
						$payload,
						image,
						media,
						language,
						praise,
						comment_sum,
						star_sum,
						read_sum,
						reward_sum,
						utctime,
						url
		FROM wet_content_sh 
		WHERE tip_id = '$tip_id' LIMIT 1";

        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['shTipid'] 		= $tip_id;
			$sender_id	  			= $row-> sender_id;
			$operation				= mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount				= $strCount ? $operation : $row->payload;
			$deleteXss				= DisposeModel::delete_xss($isStrCount);
			$data['payload']		= DisposeModel::sensitive($deleteXss);
			$data['contractId']		= $row-> contract_id;
			$data['type']			= $row-> type;
			$data['image']   		= $row->image ? "{$shApiUrl}{$row->image}" : $row->media;
			$data['media']   		= $row->media ?? "";
			$data['url']			= $row->url ?? "";
			$data['simpleUrl']		= mb_strlen($row->url,'UTF8') >= 36 ? mb_substr($row->url, 0, 36).'...' : $row->url;
			$data['language']		= $row->language ?? "";
			$data['utcTime']		= (int) $row-> utctime;
			$data['commentNumber']  = (int) $row-> comment_sum;
			$data['praise']			= (int) $row-> praise;
			$data['star']			= (int) $row-> star_sum;
			$data['read']			= (int) $row-> read_sum;
			$data['reward']			= $row-> reward_sum;
			if (isset($opt['rewardList'])) {
				$data['rewardList']	= RewardModel::rewardList($tip_id);
			}
			if (isset($opt['userLogin'])) {
				$data['isPraise']	= ValidModel::isPraise($tip_id, $opt['userLogin']);
				$data['isStar']		= ValidModel::isStar($tip_id, $opt['userLogin']);
				$data['isFocus']	= ValidModel::isFocus($sender_id, $opt['userLogin']);
			} else {
				$data['isPraise']	= false;
				$data['isStar']		= false;
				$data['isFocus']	= false;
			}
			$data['source']			= $row->source;
			$data['users']			= UserModel::getUser($sender_id);
			if (isset($opt['read'])) {
				$upReadSql = "UPDATE wet_content_sh SET read_sum = read_sum + 1 WHERE tip_id = '$tip_id'";
				ComModel::db()-> query($upReadSql);
			}
			
        }
    	return $data;
    }

	public static function simpleContent($tip_id, $opt=[])
	{//获取简单主贴内容
		$bsConfig = ConfigModel::backendConfig();
		$shApiUrl = $bsConfig['superheroApiUrl'];

		if ( (int) $opt['substr'] ) {
			$payload  = "substring(payload for '$opt[substr]') as payload";
			$strCount = true;
		} else {
			$payload  = "payload";
		}

		$sql = "SELECT sender_id,
						$payload,
						image,
						media
				FROM wet_content_sh 
				WHERE tip_id='$tip_id' LIMIT 1";

        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['shTipid'] = $tip_id;
			$sender_id	  	 = $row-> sender_id;
			$operation		 = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount		 = $strCount ? $operation : $row->payload;
			$deleteXss		 = DisposeModel::delete_xss($isStrCount);
			$data['payload'] = DisposeModel::sensitive($deleteXss);
			$data['image']   = $row->image ? "{$shApiUrl}{$row->image}" : $row->media;
			$data['media']   = $row->media ?? "";
			$data['users']['nickname'] = UserModel::getName($sender_id);
			$data['users']['userAddress'] = $sender_id;
        }
    	return $data;
    }

}

