<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	UserModel,
	ValidModel
};

class AensModel extends Model {
//AENS Model

	public function __construct(){
		//parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->UserModel  = new UserModel();
		$this->ValidModel = new ValidModel();
		$this->tablename  = "wet_users";
	}

	public function insertUserAens($address, $aens)
	{//写入AENS
		$this->UserModel-> userPut($address);
		$exist = $this->ValidModel-> isAddressAens($aens);
		if ($exist) {
			$verify = $this->ValidModel-> isAddressSameAens($address, $aens);
			if ($verify) return;
			$remove = "UPDATE $this->tablename SET default_aens = '' WHERE default_aens ilike '$aens'";
			$this->db-> query($remove);
		}
		$update = "UPDATE $this->tablename SET default_aens = '$aens' WHERE address = '$address'";
		$this->db-> query($update);
	}

}

