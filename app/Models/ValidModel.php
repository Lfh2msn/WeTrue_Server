<?php 
namespace App\Models;

use App\Models\ComModel;
use App\Models\Config\AdminConfig;

class ValidModel
{//验证Model

	public static function isContentHash($hash)
	{//主贴Hash是否存在
		$sql   = "SELECT hash FROM wet_content WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isCommentHash($hash)
	{//评论Hash是否存在
		$sql   = "SELECT hash FROM wet_comment WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isReplyHash($hash)
	{//回复Hash是否存在
		$sql   = "SELECT hash FROM wet_reply WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isPraise($hash, $address)
	{//点赞是否存在
		$sql   = "SELECT hash FROM wet_praise WHERE hash = '$hash' and sender_id = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isNickname($nickname)
	{//昵称是否存在
		$sql   = "SELECT nickname FROM wet_users WHERE nickname ilike '$nickname' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isUserAens($address)
	{//address是否存在AENS
		$sql   = "SELECT default_aens FROM wet_users WHERE address = '$address' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->default_aens ? true : false;
	}

	public static function isAddressSameAens($address, $aens)
	{//Address是否和Aens匹配，且结果只有1个
		$sql   = "SELECT default_aens, address FROM wet_users WHERE default_aens ilike '$aens'";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		$count = $row->count;//数量
		$result = false;
		if ( ($count == 1) && ($address == $row->address) ) $result = true;
		return $result;
	}

	public static function isAddressAens($aens)
	{//AENS是否存在
		$sql   = "SELECT default_aens FROM wet_users WHERE default_aens ilike '$aens'";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->default_aens ? true : false;
	}

	public static function isUser($address)
	{//用户地址是否存在
		$sql   = "SELECT address FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isNewUser($address)
	{//用户是否新账户
		$sql   = "SELECT uactive FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		$ac	   = $row->uactive;
		if ($ac < 50) {
			$sql   = "SELECT count(hash) FROM wet_content WHERE sender_id = '$address'";
			$query = ComModel::db()->query($sql);
			$row   = $query->getRow();
			$count = $row->count;//数量
			if ($count < 3) return true;
		}
		return false;
	}

	public static function isAuthUser($address)
	{//用户ID是否认证
		$sql   = "SELECT is_auth FROM wet_users WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->is_auth ? true : false;
	}

	public static function isAdmin($address)
	{//管理员校验
		$bsConfig = AdminConfig::config();
		$admin_1  = $bsConfig['adminUser_1'];
		$admin_2  = $bsConfig['adminUser_2'];
		$admin_3  = $bsConfig['adminUser_3'];
		if($address === $admin_1 || $address === $admin_2 || $address === $admin_3) {
			return true;
		} else {
			return false;
		}
	}

	public static function isRewardHash($hash)
	{//打赏Hash是否存在
		$sql   = "SELECT hash FROM wet_reward WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isTempHash($hash)
	{//临时Hash是否存在
		$sql   = "SELECT tp_hash FROM wet_temp WHERE tp_hash = '$hash' ORDER BY tp_time DESC LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->tp_hash ? true : false;
	}

	public static function isStar($hash, $address)
	{//收藏是否存在
		$sql   ="SELECT hash FROM wet_star WHERE hash = '$hash' AND sender_id = '$address' LIMIT 1";
        $query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
		return $row ? true : false;
	}

	public static function isFocus($focus,$my_id)
	{//关注是否存在
		$sql   = "SELECT focus, fans FROM wet_focus WHERE fans = '$my_id' AND focus = '$focus' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isTopicState($keywords)
	{//话题状态是否正常
		$sql   = "SELECT state FROM wet_topic_tag WHERE keywords = '$keywords' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isComplain($hash)
	{//投诉hash是否存在
		$sql   = "SELECT hash FROM wet_complain WHERE hash = '$hash' LIMIT 1";
		$query = ComModel::db()->query($sql);
        $row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isOpenVipState($address)
	{//开通VIP提交状态
		$sql   = "SELECT tp_sender_id FROM wet_temp WHERE tp_sender_id = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isVipAddress($address)
	{//是否开通VIP
		$sql   = "SELECT is_vip FROM wet_users_vip WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return isset($row->is_vip) ? true : false;
	}

	public static function isVipAccount($address)
	{//是否存在VIP账户
		$sql   = "SELECT address FROM wet_users_vip WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isMapAddress($address)
	{//映射挖矿地址是否存在
		$sql   = "SELECT address FROM wet_mapping WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isMapState($address)
	{//映射挖矿状态是否存在
		$sql   = "SELECT state FROM wet_mapping WHERE address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->state ? true : false;
	}

	public static function isBloomHash($hash)
    {//过滤Hash是否存在
        $sql   = "SELECT bf_hash FROM wet_bloom WHERE bf_hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
        $row   = $query->getRow();
        return $row ? true : false;
    }

	public static function isBloomAddress($address)
    {//过滤address是否存在
        $sql   = "SELECT bf_address FROM wet_bloom WHERE bf_address = '$address' LIMIT 1";
        $query = ComModel::db()->query($sql);
        $row   = $query->getRow();
        return $row ? true : false;
    }

	public static function isBloomIp($ip)
	{//过滤IP是否存在
		$sql   = "SELECT bf_ip FROM wet_bloom WHERE bf_ip = '$ip' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isAmountVip($address)
	{//费率vip用户是否存在
		$sql   = "SELECT address FROM wet_amount WHERE address = '$address' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

	public static function isWecomUserId($user_id)
	{//企业微信ID是否存在
		$sql   = "SELECT wecom_user_id FROM wet_wecom_users WHERE wecom_user_id = '$user_id' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->wecom_user_id ? true : false;
	}

	public static function isWecomAddress($address)
	{//企业微信Adderss是否存在
		$sql   = "SELECT wecom_user_id FROM wet_wecom_users WHERE address = '$address' OR wecom_address = '$address' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}
	public static function isWecomMnemonic($user_id)
	{//企业微信 Adderss 创建钱包是否存在
		$sql   = "SELECT wecom_mnemonic FROM wet_wecom_users WHERE wecom_user_id = '$user_id' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row->wecom_mnemonic ? true : false;
	}

	public static function isRandomAvatarHash($hash)
	{//是否存在随机头像Hash
		$sql   = "SELECT hash FROM wet_random_Avatar WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? true : false;
	}

}

