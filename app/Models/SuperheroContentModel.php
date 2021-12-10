<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\ValidModel;
use App\Models\RewardModel;

class SuperheroContentModel extends ComModel
{//主贴Model

	private $tablename;

	public function __construct()
	{
        parent::__construct();
		$this->DisposeModel	= new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->UserModel	= new UserModel();
		$this->RewardModel	= new RewardModel();
		$this->ConfigModel	= new ConfigModel();
		$this->tablename 	= "wet_content_sh";
    }

	public function txContent($tip_id, $opt = [])
	{//获取主贴内容
		$bsConfig = $this->ConfigModel-> backendConfig();
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
		FROM $this->tablename 
		WHERE tip_id = '$tip_id' LIMIT 1";

        $query = $this-> db-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['shTipid'] 		= $tip_id;
			$sender_id	  			= $row-> sender_id;
			$operation				= mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount				= $strCount ? $operation : $row->payload;
			$deleteXss				= $this->DisposeModel-> delete_xss($isStrCount);
			$data['payload']		= $this->DisposeModel-> sensitive($deleteXss);
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
			if ($opt['rewardList']) {
				$data['rewardList']	= $this->RewardModel-> rewardList($tip_id);
			}
			if ($opt['userLogin']) {
				$data['isPraise']	= $this->ValidModel-> isPraise($tip_id, $opt['userLogin']);
				$data['isStar']		= $this->ValidModel-> isStar($tip_id, $opt['userLogin']);
				$data['isFocus']	= $this->ValidModel-> isFocus($sender_id, $opt['userLogin']);
			} else {
				$data['isPraise']	= false;
				$data['isStar']		= false;
				$data['isFocus']	= false;
			}
			$data['source']			= $row->source;
			$data['users']			= $this->UserModel-> getUser($sender_id);
			if ($opt['read']) {
				$upReadSql = "UPDATE $this->tablename SET read_sum = read_sum + 1 WHERE tip_id = '$tip_id'";
				$this->db-> query($upReadSql);
			}
			
        }
    	return $data;
    }

	public function simpleContent($tip_id, $opt=[])
	{//获取简单主贴内容
		$bsConfig = $this->ConfigModel-> backendConfig();
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
				FROM $this->tablename 
				WHERE tip_id='$tip_id' LIMIT 1";

        $query = $this-> db-> query($sql);
		$row   = $query-> getRow();
        if ($row) {
			$data['shTipid'] = $tip_id;
			$sender_id	  	 = $row-> sender_id;
			$operation		 = mb_strlen($row->payload,'UTF8') >= $opt['substr'] ? $row->payload.'...' : $row->payload;
			$isStrCount		 = $strCount ? $operation : $row->payload;
			$deleteXss		 = $this->DisposeModel-> delete_xss($isStrCount);
			$data['payload'] = $this->DisposeModel-> sensitive($deleteXss);
			$data['image']   = $row->image ? "{$shApiUrl}{$row->image}" : $row->media;
			$data['media']   = $row->media ?? "";
			$data['users']['nickname'] = $this->UserModel-> getName($sender_id);
			$data['users']['userAddress'] = $sender_id;
        }
    	return $data;
    }

}

