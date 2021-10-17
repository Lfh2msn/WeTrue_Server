<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\ValidModel;

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

	public function star($hash, $select)
	{//收藏
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if (!$isAkToken) {
			return $this->DisposeModel-> wetJsonRt(401,'error_login',[]);
		}

		$this->tablename = 'wet_content';
		$whereHash = 'hash';

		if ($select === 'shTipidStar') {
			$this->tablename = 'wet_content_sh';
			$whereHash = 'tip_id';
		}

		$data = [];
		$verify = $this->ValidModel-> isStar($hash, $akToken);
		if (!$verify) {
			$starSql    = "INSERT INTO $this->wet_star(hash, sender_id) VALUES ('$hash', '$akToken')";
			$upContent  = "UPDATE $this->tablename SET star_sum = star_sum + 1 WHERE $whereHash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum + 1 WHERE address = '$akToken'";
			$isStar	    = true;
		} else {
			$starSql    = "DELETE FROM $this->wet_star WHERE hash = '$hash' AND sender_id = '$akToken'";
			$upContent  = "UPDATE $this->tablename SET star_sum = star_sum - 1 WHERE $whereHash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum - 1 WHERE address = '$akToken'";
			$isStar	    = false;
		}
		$this->db-> query($starSql);
		$this->db-> query($upContent);
		$this->db-> query($upUsers);
		//入库行为记录
		$insetrBehaviorDate = [
			'address' => $akToken,
			'hash'    => $hash,
			'thing'   => 'isStar'
		];
		$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
		$getStarSql = "SELECT star_sum FROM $this->tablename WHERE hash = '$hash' LIMIT 1";
		$query 		= $this->db-> query($getStarSql);
		$row		= $query-> getRow();
		$data['star']   = (int)$row->star_sum;
		$data['isStar'] = $isStar;
		return $this->DisposeModel-> wetJsonRt(200,'success',$data);
	}

}

