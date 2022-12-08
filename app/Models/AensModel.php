<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ValidModel
};

class AensModel
{//AENS Model

	public static function insertUserAens($address, $aens)
	{//写入AENS
		UserModel::userPut($address);
		$exist = ValidModel::isAddressAens($aens);
		if ($exist) {
			$verify = ValidModel::isAddressSameAens($address, $aens);
			if ($verify) return;
			$remove = "UPDATE wet_users SET default_aens = '' WHERE default_aens ilike '$aens'";
			ComModel::db()-> query($remove);
		}
		$update = "UPDATE wet_users SET default_aens = '$aens' WHERE address = '$address'";
		ComModel::db()-> query($update);
	}

}

