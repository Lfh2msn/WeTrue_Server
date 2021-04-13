<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;

class StarModel extends Model {

	public function __construct(){
		parent::__construct();
		$this->tablename = "wet_star";
		$this->DisposeModel = new DisposeModel();
	}

    public function isStar($hash, $address)
	{//获取收藏状态
		$sql   ="SELECT hash FROM $this->tablename WHERE hash='$hash' AND sender_id='$address' LIMIT 1";
        $query = $this->db-> query($sql);
		$row   = $query-> getRow();
		if ($row) {
			return true;
        }else{
			return false;
		}
	}

	public function star($hash)
	{//收藏
		$data['code'] = 200;
		$akToken = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if(!$isAkToken){
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		$data['data'] = [];
		if(!$hash){
			$data['msg'] = 'error_hash';
			return json_encode($data);
		}

		$verify = $this->isStar($hash, $akToken);
		if(!$verify){
			$starSql   = "INSERT INTO $this->tablename(hash, sender_id) VALUES ('$hash', '$akToken')";
			$updataSql = "UPDATE wet_content SET star = star+1 WHERE hash = '$hash'";
		}else{
			$starSql   = "DELETE FROM $this->tablename WHERE hash = '$hash' AND sender_id = '$akToken'";
			$updataSql = "UPDATE wet_content SET star = star-1 WHERE hash = '$hash'";
		}
		$this->db-> query($starSql);
		$this->db-> query($updataSql);
		//入库行为记录
		$starBehaviorSql = "INSERT INTO wet_behavior(address, thing, hash) 
								VALUES ('$akToken', 'isStar', '$hash')";
		$this->db->query($starBehaviorSql);
		$getStarSql = "SELECT star FROM wet_content WHERE hash = '$hash' LIMIT 1";
		$query = $this->db-> query($getStarSql);
		$row = $query-> getRow();
		$data['data']['star']  = (int)$row->star;
		$isStar = $this->isStar($hash, $akToken);
		$data['data']['isStar'] = $isStar;
		$data['msg'] = 'success';

		return json_encode($data);
	}

}

