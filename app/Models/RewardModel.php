<?php 
namespace App\Models;

use App\Models\{
	ComModel,
	UserModel,
	ValidModel,
	DisposeModel,
	MsgModel
};

class RewardModel
{//打赏Model

	public function __construct(){
		$this->UserModel    = new UserModel();
		$this->wet_temp     = "wet_temp";
		$this->wet_content  = "wet_content";
		$this->wet_content_sh = "wet_content_sh";
		$this->wet_users    = "wet_users";
		$this->wet_reward   = "wet_reward";
    }

	public function rewardList($hash)
	{//打赏列表
		$sql = "SELECT hash,
						amount,
						sender_id,
						block_height
					FROM $this->wet_reward WHERE to_hash = '$hash' ORDER BY block_height DESC LIMIT 50";
        $query     = ComModel::db()->query($sql);
		$getResult = $query->getResult();
		$data = [];
		foreach ($getResult as $row) {
			$detaila['hash']         = $row->hash;
			$detaila['amount']       = $row->amount;
			$detaila['nickname']     = $this->UserModel-> getName($row->sender_id);
			$detaila['sender_id']    = $row->sender_id;
			$detaila['block_height'] = $row->block_height;
			$data[] = $detaila;
		}
		return $data;
	}

	public function simpleReward($hash)
	{//简单打赏信息
		$sql = "SELECT hash,
						amount,
						sender_id
					FROM $this->wet_reward WHERE hash = '$hash' LIMIT 1";
        $query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		$data['hash']      = $row->hash;
		$data['amount']    = $row->amount;
		$data['nickname']  = $this->UserModel-> getName($row->sender_id);
		$data['sender_id'] = $row->sender_id;
		return $data;
	}

	public function rewardPut($json)
	{//打赏入库处理
		$hash		  = $json['txhash'];
		$toHash 	  = $json['payload']['toHash'] ?? $json['payload']['to_hash'];
		$sender_id    = $json['sender_id'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = $json['amount'];
		$block_height = (int)$json['block_height'];

		$isRewardHash = ValidModel::isRewardHash($hash);
		if ($isRewardHash) {
			$this->deleteTemp($hash);
			return;
		}

		$isShid = DisposeModel::checkSuperheroTipid($toHash);
		if ($isShid) { //SH ID 处理
			$sql = "SELECT sender_id FROM $this->wet_content_sh WHERE tip_id = '$toHash' LIMIT 1";
		} else {
			$sql = "SELECT sender_id FROM $this->wet_content WHERE hash = '$toHash' LIMIT 1";
		}
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		$conID = $row-> sender_id;

		if ($row && $conID == $recipient_id) {
			$inData = [
				'hash'	       => $hash,
				'to_hash'      => $toHash,
				'amount' 	   => $amount,
				'sender_id'    => $sender_id,
				'block_height' => $block_height
			];
			ComModel::db()->table($this->wet_reward)->insert($inData);
			if ($isShid) { //SH ID 处理
				$upContSql = "UPDATE $this->wet_content_sh SET reward_sum = (reward_sum + $amount) WHERE tip_id = '$toHash'";
			} else {
				$upContSql = "UPDATE $this->wet_content SET reward_sum = (reward_sum + $amount) WHERE hash = '$toHash'";
			}
			
			$upUserSql = "UPDATE $this->wet_users SET reward_sum = (reward_sum + $amount) WHERE address = '$sender_id'";
			ComModel::db()-> query($upContSql);
			ComModel::db()-> query($upUserSql);
			$this->deleteTemp($hash);

			//写入消息
			$msgData = [
				'hash' 		   => $hash,
				'toHash' 	   => $toHash,
				'type'	   	   => 'reward',
				'sender_id'	   => $sender_id,
				'recipient_id' => $conID
			];
			(new MsgModel())-> addMsg($msgData);
		}
	}

	public function deleteTemp($hash)
	{//删除临时缓存
		$delete = "DELETE FROM $this->wet_temp WHERE tp_hash = '$hash'";
		ComModel::db()->query($delete);
	}
}

