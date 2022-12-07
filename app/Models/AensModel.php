<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ValidModel
};

class AensModel
{//AENS Model

	public function __construct(){
		//parent::__construct();
		//$this->db = Database::connect('default');
		$this->tablename  = "wet_users";
	}

	public function insertUserAens($address, $aens)
	{//写入AENS
		UserModel::userPut($address);
		$exist = ValidModel::isAddressAens($aens);
		if ($exist) {
			$verify = ValidModel::isAddressSameAens($address, $aens);
			if ($verify) return;
			$remove = "UPDATE $this->tablename SET default_aens = '' WHERE default_aens ilike '$aens'";
			ComModel::db()-> query($remove);
		}
		$update = "UPDATE $this->tablename SET default_aens = '$aens' WHERE address = '$address'";
		ComModel::db()-> query($update);
	}

}

