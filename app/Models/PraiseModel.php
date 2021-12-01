<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;
use App\Models\ValidModel;

class PraiseModel extends Model {
//点赞Model

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->UserModel    = new UserModel();
		$this->ConfigModel  = new ConfigModel();
		$this->DisposeModel = new DisposeModel();
		$this->ValidModel   = new ValidModel();
		$this->wet_behavior = "wet_behavior";
    }
	
	public function praise($hash, $type)
	{//点赞
		$akToken = $_SERVER['HTTP_AK_TOKEN'];
		$isAkToken = $this->DisposeModel-> checkAddress($akToken);
		if ( !$isAkToken ) {
			return $this->DisposeModel-> wetJsonRt(401, 'error_login');
		}
		
		$whereHash = 'hash';
		if ( $type === 'topic' ) {
			$this->tablename = 'wet_content';

		} elseif ( $type === 'comment' ) {
			$this->tablename = 'wet_comment';

		} elseif ( $type === 'reply' ) {
			$this->tablename = 'wet_reply';

		} elseif ( $type === 'shTipid' ) {
			$this->tablename = 'wet_content_sh';
			$whereHash = 'tip_id';
		} else {
			return $this->DisposeModel-> wetJsonRt(401, 'error_type');
		}

		$data = [];
		$isHashSql = "SELECT $whereHash,praise FROM $this->tablename WHERE $whereHash = '$hash' LIMIT 1";
		$query = $this->db-> query($isHashSql);
		$row   = $query-> getRow();
		if(!$row) {
			$msg = 'error_hash_or_id';
		} else {
			$verify = $this->ValidModel-> isPraise($hash, $akToken);
			if(!$verify) {
				$updateSql = "UPDATE $this->tablename SET praise = praise + 1 WHERE $whereHash = '$hash'";
				$praiseSql = "INSERT INTO wet_praise(hash, sender_id) VALUES ('$hash', '$akToken')";
				$e = TRUE;
			} else {
				$updateSql = "UPDATE $this->tablename SET praise = praise - 1 WHERE $whereHash = '$hash'";
				$praiseSql = "DELETE FROM wet_praise WHERE hash = '$hash' AND sender_id = '$akToken'";
				$e = FALSE;
			}
			$this->db->query($updateSql);
			$this->db->query($praiseSql);
			//用户活跃入库
			$bkConfig  = $this->ConfigModel-> backendConfig();
			$pActive   = $bkConfig['praiseActive'];
			$countSql  = "SELECT count(hash) AS count_pick FROM wet_praise WHERE sender_id = '$akToken' AND praise_time >= now()-interval '1 D'";
			$countqy   = $this->db-> query($countSql);
			$countPick = $countqy-> getRow()-> count_pick;
			if ($countPick <= 20) { //24小时内小于20赞
				$this->UserModel-> userActive($akToken, $pActive, $e);
			}
			//入库行为记录
			$insetrBehaviorDate = [
				'address'   => $akToken,
				'thing'     => 'isPraise',
				'influence' => $pActive,
				'toaddress' => $hash
			];
			$this->db->table($this->wet_behavior)->insert($insetrBehaviorDate);
			$praise = (int)$row->praise;
			if ($e) {
				$praiseSum = $praise + 1;
			} else {
				$praiseSum = $praise - 1;
			}
			$data['praise'] = $praiseSum;
			$isPraise = $this->ValidModel-> isPraise($hash, $akToken);
			$data['isPraise'] = $isPraise;
			$msg = 'success';
		}
		return $this->DisposeModel-> wetJsonRt(200, $msg, $data);
	}
}

