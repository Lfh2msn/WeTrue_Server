<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\DisposeModel;
use App\Models\hashReadModel;

class ComplainModel extends Model {
//投诉Model
	public function __construct(){
		parent::__construct();
		$this->DisposeModel  = new DisposeModel();
		$this->hashReadModel = new hashReadModel();
		$this->wet_complain  = 'wet_complain';
		$this->wet_bloom     = 'wet_bloom';
		$this->wet_behavior  = 'wet_behavior';
	}

	public function txHash($hash)
	{//投诉hash入库
		$data['code'] = 200;
		$akToken   = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if(!$isAkToken){
			$data['msg'] = 'error_login';
			return json_encode($data);
		}

		$bloomSql = "SELECT bf_hash FROM $this->wet_bloom WHERE bf_hash = '$hash' LIMIT 1";
		$query    = $this->db-> query($bloomSql);
		$row      = $query-> getRow();
		if($row){
			$deleteReportSql = "DELETE FROM $this->wet_complain WHERE cp_hash = '$hash'";
			$this->db-> query($deleteReportSql);
			$data['msg'] = 'repeat';
			return json_encode($data);
        }
		$rpSenderId = $this->hashReadModel-> getSenderId($hash);  //获取tx发送人ID
        if(empty($rpSenderId)){
			$data['msg'] = 'error_unknown';
        	return json_encode($data);
        }
		
		$reportSql  = "SELECT cp_hash, cp_count FROM $this->wet_complain WHERE cp_hash = '$hash' LIMIT 1";
		$query      = $this->db-> query($reportSql);
		$row        = $query-> getRow();
		if($row){
			$updateReportSql = "UPDATE $this->wet_complain SET cp_count = cp_count+1 WHERE cp_hash = '$hash'";
        }else{
			$updateReportSql = "INSERT INTO $this->wet_complain(
									cp_hash, cp_sender_id, cp_count
								) VALUES (
									'$hash', '$rpSenderId', '1'
								)";
		}

		//入库行为记录
		$behaviorSql = "INSERT INTO $this->wet_behavior(
							address, hash, thing, toaddress
						) VALUES (
							'$akToken', '$hash', 'Complain', '$rpSenderId'
						)";

		$this->db-> query($updateReportSql);
		$this->db->query($behaviorSql);

		$data['msg']  = 'success';
		return json_encode($data);

	}

}

