<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\{
	UserModel,
	ConfigModel,
	ValidModel,
	DisposeModel,
	MsgModel
};
use App\Models\Get\GetAeknowModel;
use App\Models\Config\AeTokenConfig;

class RewardModel extends Model {
//打赏Model

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
		$this->ValidModel   = new ValidModel();
		$this->UserModel    = new UserModel();
		$this->DisposeModel = new DisposeModel();
		$this->GetAeknowModel = new GetAeknowModel();
		$this->AeTokenConfig  = new AeTokenConfig();
		$this->wet_temp     = "wet_temp";
		$this->wet_content  = "wet_content";
		$this->wet_content_sh = "wet_content_sh";
		$this->wet_users    = "wet_users";
		$this->wet_reward   = "wet_reward";
    }

	public function rewardList($hash)
	{//打赏列表
		$sql   = "SELECT hash,
						 amount,
						 sender_id,
						 block_height
					FROM $this->wet_reward WHERE to_hash = '$hash' ORDER BY block_height DESC LIMIT 50";
        $query     = $this->db->query($sql);
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
		$sql   = "SELECT hash,
						 amount,
						 sender_id
					FROM $this->wet_reward WHERE hash = '$hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		$data['hash']      = $row->hash;
		$data['amount']    = $row->amount;
		$data['nickname']  = $this->UserModel-> getName($row->sender_id);
		$data['sender_id'] = $row->sender_id;
		return $data;
	}
	
	public function reward($hash, $to_hash)
	{//打赏
		$tp_type   = "reward";
		$isRewardHash = $this->ValidModel-> isRewardHash($hash);
		if ($isRewardHash) {
			return $this->DisposeModel-> wetJsonRt(406, 'reward_hash_repeat');
		}

		$isTempHash = $this->ValidModel-> isTempHash($hash);
		if ($isTempHash) {
			echo $this->DisposeModel-> wetJsonRt(406, 'error_repeat_temp');
		} else { //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_to_hash, tp_type) VALUES ('$hash', '$to_hash', '$tp_type')";
			$this->db->query($insertTempSql);
			echo $this->DisposeModel-> wetJsonRt(200);
		}

		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '3 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$hashSql = "SELECT tp_hash, tp_to_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC";
		$hashqy  = $this->db-> query($hashSql);
		$getRes  = $hashqy-> getResult();
		foreach ($getRes as $row) {
			$tp_hash   = $row-> tp_hash;
			$tp_toHash = $row-> tp_to_hash;
			$this->getReward($tp_hash, $tp_toHash);
		}
	}

	public function getReward($hash, $to_hash)
	{//打赏数据获取处理
		$isRewardHash = $this->ValidModel-> isRewardHash($hash);
		if ($isRewardHash) {
			$this->deleteTemp($hash);
			$logMsg = "error_reward_repeat--hash：{$hash}\r\nto_hash：{$to_hash}\r\n";
			$logPath = "log/reward/{date('Y-m')}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return;
		}

		$json = $this->GetAeknowModel->tokenPayloadTx($hash);
		if (empty($json)) {
			$logMsg = "error_aeknow_api--hash：{$hash}\r\nto_hash：{$to_hash}\r\n";
			$logPath = "log/reward/{date('Y-m')}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return;
        }

		$tokenName   = 'WTT';
		$contractId  = $this->AeTokenConfig-> getContractId($tokenName);
		$return_type = $json['return_type'];
		$contract_id = $json['contract_id'];

		if ($contract_id != $contractId || $return_type != 'ok') {
			$this->deleteTemp($hash);
			$logMsg = "error_reward_contractIdOrType--hash：{$hash}\r\nto_hash：{$to_hash}\r\n";
			$logPath = "log/reward/{date('Y-m')}.txt";
			$this->DisposeModel->wetFwriteLog($logMsg, $logPath);
			return;
		}

		$this->rewardPut($json, $to_hash);
	}

	public function rewardPut($json, $to_hash)
	{//打赏入库处理
		$hash		  = $json['txhash'];
		$sender_id    = $json['sender_id'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = $json['amount'];
		$block_height = (int)$json['block_height'];

		$isRewardHash = $this->ValidModel-> isRewardHash($hash);
		if ($isRewardHash) {
			$this->deleteTemp($hash);
			return;
		}

		$isShid = $this->DisposeModel-> checkSuperheroTipid($to_hash);
		if ($isShid) { //SH ID 处理
			$sql = "SELECT sender_id FROM $this->wet_content_sh WHERE tip_id = '$to_hash' LIMIT 1";
		} else {
			$sql = "SELECT sender_id FROM $this->wet_content WHERE hash = '$to_hash' LIMIT 1";
		}
		$query = $this->db->query($sql);
		$row   = $query->getRow();
		$conID = $row-> sender_id;

		if ($row && $conID == $recipient_id) {
			$inData = [
				'hash'	       => $hash,
				'to_hash'      => $to_hash,
				'amount' 	   => $amount,
				'sender_id'    => $sender_id,
				'block_height' => $block_height
			];
			$this->db->table($this->wet_reward)->insert($inData);
			if ($isShid) { //SH ID 处理
				$upContSql = "UPDATE $this->wet_content_sh SET reward_sum = (reward_sum + $amount) WHERE tip_id = '$to_hash'";
			} else {
				$upContSql = "UPDATE $this->wet_content SET reward_sum = (reward_sum + $amount) WHERE hash = '$to_hash'";
			}
			
			$upUserSql = "UPDATE $this->wet_users SET reward_sum = (reward_sum + $amount) WHERE address = '$sender_id'";
			$this->db-> query($upContSql);
			$this->db-> query($upUserSql);
			$this->deleteTemp($hash);

			//写入消息
			$msgData = [
				'hash' 		   => $hash,
				'to_hash' 	   => $to_hash,
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
		$this->db->query($delete);
	}
}

