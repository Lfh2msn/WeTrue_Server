<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	DisposeModel,
	ValidModel
};

class StarModel extends Model {
//收藏Model

	public function __construct(){
		//parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->DisposeModel = new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->wet_star     = "wet_star";
		$this->wet_users	= "wet_users";
		$this->wet_behavior = "wet_behavior";
	}

	public function star($address, $hash, $action, $select)
	{//收藏
		$this->tablename = 'wet_content';
		$whereHash = 'hash';

		if ($select == "shTipidStar") {
			$this->tablename = 'wet_content_sh';
			$whereHash = 'tip_id';
		}

		$verify = $this->ValidModel-> isStar($hash, $address);
		if (!$verify && $action == 'true') {
			$starSql    = "INSERT INTO $this->wet_star(hash, sender_id) VALUES ('$hash', '$address')";
			$upContent  = "UPDATE $this->tablename SET star_sum = star_sum + 1 WHERE $whereHash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum + 1 WHERE address = '$address'";
		}

		elseif ($verify && $action == 'false') {
			$starSql    = "DELETE FROM $this->wet_star WHERE hash = '$hash' AND sender_id = '$address'";
			$upContent  = "UPDATE $this->tablename SET star_sum = star_sum - 1 WHERE $whereHash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum - 1 WHERE address = '$address'";
		}

		else die("star Error");

		$this->db-> query($starSql);
		$this->db-> query($upContent);
		$this->db-> query($upUsers);
	}

}

