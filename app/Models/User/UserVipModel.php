<?php namespace App\Models\User;

use App\Models\ComModel;
use App\Models\AirdropModel;
use App\Models\ValidModel;
use App\Models\DisposeModel;
use App\Models\ConfigModel;

class UserVipModel extends ComModel
{//用户Model

	public function __construct(){
        parent::__construct();
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel	= new DisposeModel();
		$this->ValidModel	= new ValidModel();
		$this->tablename    = "wet_users";
    }

	public function userAllInfo($address, $opt=[])
	{/*获取用户完整信息分页
		opt可选参数
		[
			type => login,登录类型
		];*/
		$sql="SELECT 
					nickname,
					default_aens,
					sex,
					uactive,
					portrait,
					portrait_hash,
					last_active,
					reward_sum,
					topic_sum,
					focus_sum,
					fans_sum,
					star_sum,
					is_map,
					is_auth
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		$bsConfig = $this->ConfigModel-> backendConfig();
		if (!$row && $opt['type'] == 'login') {
			$this-> userPut($address);
			if ($bsConfig['airdropAE']) {
				(new AirdropModel())-> airdropAE($address);
			}
		}
		$userActive   = (int)$row->uactive ?? 0;
		$userReward   = $row->reward_sum ?? 0;
		$portrait 	  = $row->portrait;
		$portraitHash = $row->portrait_hash;
		$nickname     = $this->DisposeModel-> delete_xss($row->nickname);
		$nickname 	  = mb_substr($nickname, 0, 15);
		$defaultAens  = $row->default_aens;
		$data['userAddress']  = $address;
		$data['nickname']     = $nickname ?? "";
		$data['defaultAens']  = $defaultAens ?? "";
		$data['sex'] 	      = (int)$row->sex;
		$data['active'] 	  = $userActive;
		$data['reward'] 	  = $userReward;
		$data['userActive']   = $this->DisposeModel-> activeGrade($userActive);
		$data['userReward']   = $this->DisposeModel-> rewardGrade($userReward);
		$data['lastActive']   = ($userActive - $row->last_active) * $bsConfig['airdropWttRatio'];
		$data['portrait']	  = $portrait ? "/User/portrait/".$address : "/images/default_head.png";
		$data['portraitHash'] = $portraitHash ?? "";
		$data['topic'] 		  = (int)$row->topic_sum;
		$data['star'] 		  = (int)$row->star_sum;
		$data['focus'] 		  = (int)$row->focus_sum;
		$data['fans']  		  = (int)$row->fans_sum;
		$is_map = $row->is_map ? true : false;
		$data['is_map']  	  = $is_map;
		$data['isMapping']    = $is_map;
		$data['isAuth']  	  = $row->is_auth ? true : false;
		$isAdmin = $this->ValidModel-> isAdmin($address);
		if ($isAdmin) {
			$data['isAdmin']  = $isAdmin;
		}
		return $data;
	}

}

