<?php 
namespace App\Models;

//use App\Models\AirdropModel; //空投结束暂时屏蔽
use App\Models\{
	ComModel,
	FocusModel,
	ValidModel,
	DisposeModel
};
use App\Models\Config\AirdropConfig;

class UserModel
{//用户Model

    public static function getUser($address)
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
				FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row = $query->getRow();

		$data['userAddress'] = $address;
		$nickname = DisposeModel::delete_xss($row->nickname);
		$nickname = mb_substr($nickname, 0, 15);
		$defaultAens  = $row->default_aens;
		$data['nickname']   = $nickname ?? "";
		$data['defaultAens']= $defaultAens ?? "";
		$data['sex'] 	    = (int)$row->sex ?? 0;
		$userActive 	    = (int)$row->uactive;
		$userReward   		= $row->reward_sum;
		$data['active']     = $userActive ?? 0;
		$data['userActive'] = DisposeModel::activeGrade($userActive) ?? 0;;
		$data['reward'] 	= $userReward ?? 0;
		$data['userReward'] = DisposeModel::rewardGrade($userReward) ?? 0;;
		$data['avatar']     = $row->avatar ?? "";
		$is_vip = ValidModel::isVipAddress($address);
		$data['isVip']  	= $is_vip ? true : false;
		$data['isAuth']  	= $row->is_auth ? true : false;

		return $data;
	}

	public static function userAllInfo($address, $opt=[])
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
			FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row = $query->getRow();
		

		if (!$row && isset($opt['type'])) {
			if ($opt['type'] == 'login') {
				$acConfig = AirdropConfig::config();
				self::userPut($address);
				/* //空投结束暂时屏蔽
				if ($acConfig['aeOpen']) {
					(new AirdropModel())-> airdropAE($address);
				}
				*/
			}
		}
		$userActive  = $row->uactive ?? 0;
		$userReward  = $row->reward_sum ?? 0;
		$nickname    = isset($row->nickname) ? DisposeModel::delete_xss($row->nickname) : '';
		$nickname 	 = mb_substr($nickname, 0, 15);
		$defaultAens = $row->default_aens ?? '';
		$data['userAddress'] = $address;
		$data['nickname']    = $nickname ?? '';
		$data['defaultAens'] = $defaultAens ?? '';
		$data['sex'] 	     = $row->sex ?? '';
		$data['active'] 	 = $userActive;
		$data['reward'] 	 = $userReward;
		$data['userActive']  = DisposeModel::activeGrade($userActive);
		$data['userReward']  = DisposeModel::rewardGrade($userReward);
		$data['lastActive']  = ($userActive - ($row->last_active ?? 0));// * $acConfig['wttRatio'];
		$data['avatar']      = $row->avatar ?? '';
		$data['topic'] 		 = $row->topic_sum ?? 0;
		$data['star'] 		 = $row->star_sum ?? 0;
		$data['focus'] 		 = $row->focus_sum ?? 0;
		$data['fans']  		 = $row->fans_sum ?? 0;
		$is_vip = ValidModel::isVipAddress($address);
		$data['isVip']  	 = $is_vip ? true : false;
		$data['isAuth']  	 = $row->is_auth ? true : false;
		$isAdmin = ValidModel::isAdmin($address);
		if ($isAdmin) {
			$data['isAdmin'] = $isAdmin;
		}
		return $data;
	}

	public static function getName($address)
	{//获取用户昵称
		$sql   = "SELECT nickname FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
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

	public static function userActive($address, $active, $e)
	{/*用户活跃入库
		$address = 目标地址
		$active  = 数量
		$e       = true增 或 false减
	*/
		self::userPut($address);
		if ($e) {
			$updateSql = "UPDATE wet_users SET uactive = uactive + '$active' WHERE address = '$address'";
		} else {
			$updateSql = "UPDATE wet_users SET uactive = uactive - '$active' WHERE address = '$address'";
		}
		ComModel::db()->query($updateSql);
	}

	public static function userFocus($focus, $fans, $e)
	{/*用户关注用户
			$focus = 目标地址
			$fans  = 粉丝地址
			$e     = isFocus
	*/
		self::userPut($fans);
		if ($e) {
			$focusSql = "UPDATE wet_users SET focus_sum = focus_sum + 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE wet_users SET fans_sum = fans_sum + 1 WHERE address = '$focus'";
		} else {
			$focusSql = "UPDATE wet_users SET focus_sum = focus_sum - 1 WHERE address = '$fans'";
			$fansSql  = "UPDATE wet_users SET fans_sum = fans_sum - 1 WHERE address = '$focus'";
		}
		ComModel::db()->query($focusSql);
		ComModel::db()->query($fansSql);
	}

	public static function userPut($address)
	{//用户入库
		$selectSql = "SELECT address FROM wet_users WHERE address = '$address' LIMIT 1";
		$query	   = ComModel::db()->query($selectSql);
		$row	   = $query-> getRow();
		if (!$row) {
			$insertSql = "INSERT INTO wet_users(address) VALUES ('$address')";
			ComModel::db()->query($insertSql);
			$autoFans1 = 'ak_2kxt6D65giv4yNt4oa44SjW4jEXfoHMviPFvAreSEXvz25Q3QQ';
			//$autoFans2 = 'ak_AiYsw9sJVdfBCXbAAys4LiMDnXBd1BTTSi13fzpryQcXjSpsS';
			FocusModel::autoFocus($autoFans1 ,$address);
		}
	}

	public static function userDelete($address)
	{//用户删除
		$sql = "DELETE FROM wet_users WHERE address = '$address'";
		$query = ComModel::db()->query($sql);
	}

}

