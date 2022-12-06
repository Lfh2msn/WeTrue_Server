<?php namespace App\Models;

use CodeIgniter\Model;
use Config\Database;
use App\Models\{
	UserModel,
	DisposeModel,
	ValidModel
};
use App\Models\Config\ActiveConfig;

class PraiseModel extends Model {
//点赞Model

	public function __construct(){
        //parent::__construct();
		$this->db = Database::connect('default');
		$this->UserModel    = new UserModel();
		$this->ValidModel   = new ValidModel();
		$this->wet_behavior = "wet_behavior";
    }
	
	public function praise($hash, $type)
	{//点赞
		$akToken = $_SERVER['HTTP_KEY'];
		$isAkToken = DisposeModel::checkAddress($akToken);
		if ( !$isAkToken ) {
			return DisposeModel::wetJsonRt(401, 'error_login');
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
			return DisposeModel::wetJsonRt(401, 'error_type');
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
			$acConfig  = ActiveConfig::config();
			$psActive  = $acConfig['praiseActive'];
			$countSql  = "SELECT count(hash) AS count_pick FROM wet_praise WHERE sender_id = '$akToken' AND praise_time >= now()-interval '1 D'";
			$countqy   = $this->db-> query($countSql);
			$countPick = $countqy-> getRow()-> count_pick;
			if ($countPick <= 20) { //24小时内小于20赞
				$this->UserModel-> userActive($akToken, $psActive, $e);
			}
			//入库行为记录
			$insetrBehaviorDate = [
				'address'   => $akToken,
				'thing'     => 'isPraise',
				'influence' => $psActive,
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
		return DisposeModel::wetJsonRt(200, $msg, $data);
	}
}

