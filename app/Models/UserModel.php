<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\AirdropModel;
use App\Models\FocusModel;

class UserModel extends ComModel
{//用户Model

	public function __construct(){
        parent::__construct();
        $this->tablename    = 'wet_users';
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel	= new DisposeModel();
    }

	public function isUser($address)
	{//验证用户ID是否存在
		$sql   = "SELECT address FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public function isNickname($nickname)
	{//查询昵称是否存在
		$sql   = "SELECT nickname FROM $this->tablename WHERE nickname ilike '$nickname' LIMIT 1";
		$query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

    public function getUser($address)
	{//获取用户昵称、头像、、活跃、等级
		$sql="SELECT 
					nickname,
					sex,
					uactive,
					portrait,
					reward_sum,
					last_active
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		if ($row) {
			$data['userAddress'] = $address;
			$nickname = $this->DisposeModel-> delete_xss($row->nickname);
			$data['nickname']   = $nickname ?? "";
			$data['sex'] 	    = (int)$row->sex;
			$userActive 	    = (int)$row->uactive;
			$userReward   		= $row->reward_sum;
            $data['active']     = $userActive;
			$data['reward'] 	= $userReward;
			$data['userActive'] = $this->DisposeModel-> activeGrade($userActive);
			$data['userReward'] = $this->DisposeModel-> rewardGrade($userReward);
			$portrait 			= $row->portrait;
			$data['portrait']   = $portrait ? "https://api.wetrue.io/User/portrait/".$address : "https://api.wetrue.io/images/default_head.png";
			
        } else {
			die("error getUser");
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
					is_map
				FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row = $query->getRow();
		$bsConfig = $this->ConfigModel-> backendConfig();
		if (!$row && $opt['type'] == 'login') {
			$this-> userPut($address);
			$insBehSql = "INSERT INTO wet_behavior(address, thing) VALUES ('$address', 'newUserLogin')";
            $this->db->query($insBehSql);
			if ($bsConfig['airdropAE']) {
				(new AirdropModel())-> airdropAE($address);
			}
		}
		$nickname     = $this->DisposeModel-> delete_xss($row->nickname);
		$userActive   = (int)$row->uactive;
		$userReward   = $row->reward_sum;
		$portrait 	  = $row->portrait;
		$portraitHash = $row->portrait_hash;
		$data['userAddress']  = $address;
		$data['nickname']     = $nickname ?? "";
		$data['sex'] 	      = (int)$row->sex;
		$data['active'] 	  = $userActive;
		$data['reward'] 	  = $userReward;
		$data['userActive']   = $this->DisposeModel-> activeGrade($userActive);
		$data['userReward']   = $this->DisposeModel-> rewardGrade($userReward);
		$data['lastActive']   = ($userActive - $row->last_active) * $bsConfig['airdropWttRatio'];
		$data['portrait']	  = $portrait ? "https://api.wetrue.io/User/portrait/".$address : "https://api.wetrue.io/images/default_head.png";
		$data['portraitHash'] = $portraitHash ?? "";
		$data['topic'] 		  = (int)$row->topic_sum;
		$data['star'] 		  = (int)$row->star_sum;
		$data['focus'] 		  = (int)$row->focus_sum;
		$data['fans']  		  = (int)$row->fans_sum;
		$data['is_map']  	  = $row->is_map ? true : false;
		if ($opt['type'] == 'login')
		{
			$isAdmin = $this->isAdmin($address);
			if ($isAdmin) {
				$data['isAdmin']  = true;
			}
		}
		return $data;
	}

	public function getName($address)
	{//获取用户昵称
		$sql   = "SELECT nickname FROM $this->tablename WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			$nickname = $this->DisposeModel-> delete_xss($row->nickname);
			$data = $nickname ?? "";
        } else {
			return;
		}
		return $data;
	}

	public function getPortraitUrl($address)
	{//获取用户头像路径
		$sql      = "SELECT portrait FROM wet_users WHERE address = '$address' LIMIT 1";
        $query    = $this->db->query($sql);
		$row      = $query->getRow();
		$portrait = $row->portrait;
		$portrait = $portrait ? "https://api.wetrue.io/User/portrait/".$address : "https://api.wetrue.io/images/default_head.png";
		$data['code'] = 200;
		$data['data']['url'] = $portrait;
		$data['msg']  = 'success';
		return json_encode($data);
	}

	public function getPortrait($address)
	{//获取用户头像
		$sql   = "SELECT portrait FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			$data = $row->portrait ?? "";
        } else {
			return "error_address_portrait";
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
		if ( !$row ) {
			$insertSql = "INSERT INTO $this->tablename(address) VALUES ('$address')";
			$this->db->query($insertSql);
			$autoFans1 = 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ';
			//$autoFans2 = 'ak_AiYsw9sJVdfBCXbAAys4LiMDnXBd1BTTSi13fzpryQcXjSpsS';
			(new FocusModel())-> autoFocus($autoFans1 ,$address);
		}
	}

	public function isAdmin($address)
	{//管理员校验
		$bsConfig = $this->ConfigModel-> backendConfig();
		$admin_1  = $bsConfig['adminUser_1'];
		$admin_2  = $bsConfig['adminUser_2'];
		$admin_3  = $bsConfig['adminUser_3'];

		if($address === $admin_1 || $address === $admin_2 || $address === $admin_3) {
			return true;
		} else {
			return false;
		}
	}

}

