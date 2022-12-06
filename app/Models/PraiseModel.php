<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	DisposeModel,
	ValidModel
};
use App\Models\Config\ActiveConfig;

class PraiseModel
{//点赞Model

	public function __construct(){
		$this->UserModel    = new UserModel();
		$this->wet_behavior = "wet_behavior";
    }
	
	public function praise($hash, $type)
	{//点赞
		$akToken = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
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
		$query = ComModel::db()-> query($isHashSql);
		$row   = $query-> getRow();
		if(!$row) {
			$msg = 'error_hash_or_id';
		} else {
			$verify = ValidModel::isPraise($hash, $akToken);
			if(!$verify) {
				$updateSql = "UPDATE $this->tablename SET praise = praise + 1 WHERE $whereHash = '$hash'";
				$praiseSql = "INSERT INTO wet_praise(hash, sender_id) VALUES ('$hash', '$akToken')";
				$e = TRUE;
			} else {
				$updateSql = "UPDATE $this->tablename SET praise = praise - 1 WHERE $whereHash = '$hash'";
				$praiseSql = "DELETE FROM wet_praise WHERE hash = '$hash' AND sender_id = '$akToken'";
				$e = FALSE;
			}
			ComModel::db()->query($updateSql);
			ComModel::db()->query($praiseSql);
			//用户活跃入库
			$acConfig  = ActiveConfig::config();
			$psActive  = $acConfig['praiseActive'];
			$countSql  = "SELECT count(hash) AS count_pick FROM wet_praise WHERE sender_id = '$akToken' AND praise_time >= now()-interval '1 D'";
			$countqy   = ComModel::db()-> query($countSql);
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
			ComModel::db()->table($this->wet_behavior)->insert($insetrBehaviorDate);
			$praise = (int)$row->praise;
			if ($e) {
				$praiseSum = $praise + 1;
			} else {
				$praiseSum = $praise - 1;
			}
			$data['praise'] = $praiseSum;
			$isPraise = ValidModel::isPraise($hash, $akToken);
			$data['isPraise'] = $isPraise;
			$msg = 'success';
		}
		return DisposeModel::wetJsonRt(200, $msg, $data);
	}
}

