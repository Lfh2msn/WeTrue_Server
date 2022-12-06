<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	AirdropModel,
	FocusModel,
	ValidModel,
	DisposeModel,
	ConfigModel
};

class UserModel extends ComModel
{//用户Model

	public function __construct(){
        parent::__construct();
		$this->ValidModel	= new ValidModel();
		$this->tablename    = "wet_users";
		$this->wet_behavior = "wet_behavior";
    }

    public function getUser($address)
	{//获取用户昵称、头像、活跃、等级
		$sql="SELECT 
					nickname,
					default_aens,
					sex,
					uactive,
					avatar,
					reward_sum,
					last_active,
					is_auth
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$nickname = DisposeModel::delete_xss($row->nickname);
			$nickname = mb_substr($nickname, 0, 15);
			$defaultAens  = $row->default_aens;
			$data['nickname']   = $nickname ?? "";
			$data['defaultAens']= $defaultAens ?? "";
			$data['sex'] 	    = (int)$row->sex;
			$userActive 	    = (int)$row->uactive;
			$userReward   		= $row->reward_sum;
            $data['active']     = $userActive;
			$data['userActive'] = DisposeModel::activeGrade($userActive);
			$data['reward'] 	= $userReward;
			$data['userReward'] = DisposeModel::rewardGrade($userReward);
			$data['avatar']     = $row->avatar ?? "";
			$is_vip = $this->ValidModel-> isVipAddress($address);
			$data['isVip']  	= $is_vip ? true : false;
			$data['isAuth']  	= $row->is_auth ? true : false;
        }
		return $data;
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
				avatar,
				avatar_hash,
				last_active,
				reward_sum,
				topic_sum,
				focus_sum,
				fans_sum,
				star_sum,
				is_auth
			FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		$bsConfig = ConfigModel::backendConfig();
		if (!$row && $opt['type'] == 'login') {
			$this-> userPut($address);
			if ($bsConfig['airdropAE']) {
				(new AirdropModel())-> airdropAE($address);
			}
		}
		$userActive  = (int)$row->uactive ?? 0;
		$userReward  = $row->reward_sum ?? 0;
		$nickname    = DisposeModel::delete_xss($row->nickname);
		$nickname 	 = mb_substr($nickname, 0, 15);
		$defaultAens = $row->default_aens;
		$data['userAddress'] = $address;
		$data['nickname']    = $nickname ?? "";
		$data['defaultAens'] = $defaultAens ?? "";
		$data['sex'] 	     = (int)$row->sex;
		$data['active'] 	 = $userActive;
		$data['reward'] 	 = $userReward;
		$data['userActive']  = DisposeModel::activeGrade($userActive);
		$data['userReward']  = DisposeModel::rewardGrade($userReward);
		$data['lastActive']  = ($userActive - $row->last_active);// * $bsConfig['airdropWTTRatio'];
		$data['avatar']      = $row->avatar ?? "";
		$data['topic'] 		 = (int)$row->topic_sum;
		$data['star'] 		 = (int)$row->star_sum;
		$data['focus'] 		 = (int)$row->focus_sum;
		$data['fans']  		 = (int)$row->fans_sum;
		$is_vip = $this->ValidModel-> isVipAddress($address);
		$data['isVip']  	 = $is_vip ? true : false;
		$data['isAuth']  	 = $row->is_auth ? true : false;
		$isAdmin = $this->ValidModel-> isAdmin($address);
		if ($isAdmin) {
			$data['isAdmin'] = $isAdmin;
		}
		return $data;
	}

	public function getName($address)
	{//获取用户昵称
		$sql   = "SELECT nickname FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			$nickname = DisposeModel::delete_xss($row->nickname);
			$nickname = mb_substr($nickname, 0, 15);
			$data = $nickname ?? "";
        } else {
			$data = "error_address";
		}
		return $data;
	}

	public function userActive($address, $active, $e)
	{/*用户活跃入库
		$address = 目标地址
		$active  = 数量
		$e       = true增 或 false减
	*/
		$this-> userPut($address);
		if ($e) {
			$updateSql = "UPDATE $this->tablename SET uactive = uactive + '$active' WHERE address = '$address'";
		} else {
			$updateSql = "UPDATE $this->tablename SET uactive = uactive - '$active' WHERE address = '$address'";
		}
		$this->db->query($updateSql);
	}

	public function userFocus($focus, $fans, $e)
	{/*用户关注用户
			$focus = 目标地址
			$fans  = 粉丝地址
			$e     = isFocus
	*/
		$this-> userPut($fans);
		if ($e) {
			$focusSql = "UPDATE $this->tablename SET focus_sum = focus_sum + 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE $this->tablename SET fans_sum = fans_sum + 1 WHERE address = '$focus'";
		} else {
			$focusSql = "UPDATE $this->tablename SET focus_sum = focus_sum - 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE $this->tablename SET fans_sum = fans_sum - 1 WHERE address = '$focus'";
		}
		$this->db->query($focusSql);
		$this->db->query($fansSql);
	}

	public function userPut($address)
	{//用户入库
		$selectSql = "SELECT address FROM $this->tablename WHERE address = '$address' LIMIT 1";
		$query	   = $this->db->query($selectSql);
		$row	   = $query-> getRow();
		if (!$row) {
			$insertSql = "INSERT INTO $this->tablename(address) VALUES ('$address')";
			$this->db->query($insertSql);
			$autoFans1 = 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ';
			//$autoFans2 = 'ak_AiYsw9sJVdfBCXbAAys4LiMDnXBd1BTTSi13fzpryQcXjSpsS';
			(new FocusModel())-> autoFocus($autoFans1 ,$address);
		}
	}

	public function userDelete($address)
	{//用户删除
		$sql = "DELETE FROM $this->tablename WHERE address = '$address'";
		$query = $this->db->query($sql);
	}

}

