<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;

class StarModel extends Model {
//收藏Model

	public function __construct(){
		//parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->wet_star     = "wet_star";
		$this->wet_users	= "wet_users";
		$this->wet_content  = "wet_content";
		$this->DisposeModel = new DisposeModel();
	}

    public function isStar($hash, $address)
	{//获取收藏状态
		$sql   ="SELECT hash FROM $this->wet_star WHERE hash = '$hash' AND sender_id = '$address' LIMIT 1";
        $query = $this->db-> query($sql);
		$row   = $query-> getRow();
		return $row ? true : false;
	}

	public function star($hash)
	{//收藏
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if (!$isAkToken) {
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		$data['data'] = [];
		if (!$hash) {
			$data['msg'] = 'error_hash';
			return json_encode($data);
		}

		$verify = $this->isStar($hash, $akToken);
		if (!$verify) {
			$starSql    = "INSERT INTO $this->wet_star(hash, sender_id) VALUES ('$hash', '$akToken')";
			$upContent  = "UPDATE $this->wet_content SET star_sum = star_sum + 1 WHERE hash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum + 1 WHERE address = '$akToken'";
			$isStar	    = true;
		} else {
			$starSql    = "DELETE FROM $this->wet_star WHERE hash = '$hash' AND sender_id = '$akToken'";
			$upContent  = "UPDATE $this->wet_content SET star_sum = star_sum - 1 WHERE hash = '$hash'";
			$upUsers    = "UPDATE $this->wet_users SET star_sum = star_sum - 1 WHERE address = '$akToken'";
			$isStar	    = false;
		}
		$this->db-> query($starSql);
		$this->db-> query($upContent);
		$this->db-> query($upUsers);
		//入库行为记录
		$starBehaviorSql = "INSERT INTO wet_behavior(address, thing, hash) 
								VALUES ('$akToken', 'isStar', '$hash')";
		$this->db->query($starBehaviorSql);
		$getStarSql = "SELECT star_sum FROM $this->wet_content WHERE hash = '$hash' LIMIT 1";
		$query 		= $this->db-> query($getStarSql);
		$row		= $query-> getRow();
		$data['data']['star']   = (int)$row->star_sum;
		$data['data']['isStar'] = $isStar;
		$data['msg'] = 'success';

		return json_encode($data);
	}

}

