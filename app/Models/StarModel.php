<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	ValidModel
};

class StarModel
{//收藏Model

	public static function star($address, $hash, $action, $select)
	{//收藏
		$tablename = 'wet_content';
		$whereHash = 'hash';

		if ($select == "shTipidStar") {
			$tablename = 'wet_content_sh';
			$whereHash = 'tip_id';
		}

		$verify = ValidModel::isStar($hash, $address);
		if (!$verify && ($action == true || $action == 'true') ) {
			$starSql   = "INSERT INTO wet_star(hash, sender_id) VALUES ('$hash', '$address')";
			$upContent = "UPDATE $tablename SET star_sum = star_sum + 1 WHERE $whereHash = '$hash'";
			$upUsers   = "UPDATE wet_users SET star_sum = star_sum + 1 WHERE address = '$address'";
		}

		elseif ($verify && ($action == false || $action == 'false') ) {
			$starSql   = "DELETE FROM wet_star WHERE hash = '$hash' AND sender_id = '$address'";
			$upContent = "UPDATE $tablename SET star_sum = star_sum - 1 WHERE $whereHash = '$hash'";
			$upUsers   = "UPDATE wet_users SET star_sum = star_sum - 1 WHERE address = '$address'";
		}

		else return "star Error";

		ComModel::db()-> query($starSql);
		ComModel::db()-> query($upContent);
		ComModel::db()-> query($upUsers);
	}

}

