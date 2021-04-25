<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;

class PraiseModel extends Model {

	public function __construct(){
        parent::__construct();
		$this->userModel    = new UserModel();
		$this->configModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
    }

    public function isPraise($hash, $address)
	{//是否点赞
		$sql   = "SELECT hash FROM wet_praise WHERE hash='$hash' and sender_id='$address' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		if ($row) {
			return TRUE;
        }else{
			return FALSE;
		}
	}
	
	public function praise($hash, $type)
	{//点赞
		$data['code'] = 200;
		$akToken = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if(!$isAkToken){
			$data['code'] = 401;
			$data['msg']  = 'error_login';
			return json_encode($data);
		}

		$data['data'] = [];
		if($type === 'topic'){
			$this->tablename = 'wet_content';

		}else if($type === 'comment'){
			$this->tablename = 'wet_comment';

		}else if($type === 'reply'){
			$this->tablename = 'wet_reply';
			
		}else{
			$data['msg'] = 'error_type';
			return json_encode($data);
		}

		$isHashSql = "SELECT hash, praise FROM $this->tablename WHERE hash = '$hash' LIMIT 1";
		$query = $this->db-> query($isHashSql);
		$row   = $query-> getRow();
		if(!$row){
			$data['msg']  = 'error_hash';
		}else{
			$verify = $this->isPraise($hash, $akToken);
			if(!$verify){
				$updateSql = "UPDATE $this->tablename SET praise = praise+1 WHERE hash = '$hash'";
				$praiseSql = "INSERT INTO wet_praise(hash,sender_id) VALUES ('$hash','$akToken')";
				$e = TRUE;
			}else{
				$updateSql = "UPDATE $this->tablename SET praise = praise-1 WHERE hash='$hash'";
				$praiseSql = "DELETE FROM wet_praise WHERE hash = '$hash' AND sender_id = '$akToken'";
				$e = FALSE;
			}
			$this->db->query($updateSql);
			$this->db->query($praiseSql);
			//用户活跃入库
			$backendConfig = $this->configModel-> backendConfig();
			$praiseActive  = $backendConfig['praiseActive'];
			$this->userModel-> userActive($akToken, $praiseActive, $e);
			//入库行为记录
			$praiseBehaviorSql = "INSERT INTO wet_behavior(address, thing, influence, toaddress) 
									VALUES ('$akToken', 'isPraise', '$praiseActive', '$hash')";
			$this->db-> query($praiseBehaviorSql);
			$query = $this->db-> query($isHashSql);
			$row = $query-> getRow();
			$data['data']['praise']  = (int)$row->praise;
			$isPraise = $this-> isPraise($hash, $akToken);
			$data['data']['isPraise'] = $isPraise;
			$data['msg'] = 'success';
		}
		return json_encode($data);
	}
}

