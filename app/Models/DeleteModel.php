<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	DisposeModel
};

class DeleteModel
{//删除Model

	public static function deleteAll($address)
	{//删除用户全部信息
		self::deleteReward($address);
		self::deleteMsg($address);
		self::deleteFocus($address);
		self::deleteStar($address);
		self::deletePraise($address);
		self::deleteReply($address);
		self::deleteComment($address);
		self::deleteContent($address);
		self::deleteShContent($address);
		self::deleteUser($address);
		$logMsg  = date('Y-m-d')."用户被自动删除,地址:{$address}";
		$logPath = "auto_delete_user/".date('Y-m');
		DisposeModel::wetFwriteLog($logMsg, $logPath);
		return "ok";
	}

	public static function deleteUser($address)
	{//删除用户
		$sql = "DELETE FROM wet_users WHERE address = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteContent($address)
	{//删除主贴
		$sql = "DELETE FROM wet_content WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteShContent($address)
	{//删除SH主贴
		$sql = "DELETE FROM wet_content_sh WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteComment($address)
	{//删除评论
		$sql = "DELETE FROM wet_comment WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteReply($address)
	{//删除回复
		$sql = "DELETE FROM wet_reply WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteMsg($address)
	{//删除消息
		$sql = "DELETE FROM wet_message WHERE sender_id = '$address' OR recipient_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteFocus($address)
	{//删除关注
		$sql = "DELETE FROM wet_focus WHERE focus = '$address' OR fans = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteStar($address)
	{//删除收藏
		$sql = "DELETE FROM wet_star WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deletePraise($address)
	{//删除点赞
		$sql = "DELETE FROM wet_praise WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

	public static function deleteReward($address)
	{//删除打赏
		$sql = "DELETE FROM wet_reward WHERE sender_id = '$address'";
		$query = ComModel::db()->query($sql);
	}

}

