<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;
use App\Models\ConfigModel;
use App\Models\ValidModel;

class RewardModel extends Model {
//打赏Model

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel = new ConfigModel();
		$this->ValidModel  = new ValidModel();
		$this->UserModel   = new UserModel();
		$this->wet_temp    = "wet_temp";
		$this->wet_content = "wet_content";
		$this->wet_users   = "wet_users";
		$this->wet_reward  = "wet_reward";
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
	
	public function reward($hash, $to_hash)
	{//打赏
		$isRewardHash = $this->ValidModel-> isRewardHash($hash);
		if ( $isRewardHash ) {
			$data['code'] = 406;
			$data['msg']  = 'error_repeat';
			return json_encode($data);
		}

		$tp_type   = "reward";
		$isHashSql = "SELECT tp_hash FROM $this->wet_temp WHERE tp_hash = '$hash' AND tp_type = '$tp_type' LIMIT 1";
		$query     = $this->db-> query($isHashSql)-> getRow();
		if ($query) {
			$data['code'] = 406;
			$data['msg']  = 'error_repeat_temp';
			echo json_encode($data);
		} else {  //写入临时缓存
			$insertTempSql = "INSERT INTO $this->wet_temp(tp_hash, tp_to_hash, tp_type) VALUES ('$hash', '$to_hash', '$tp_type')";
			$this->db->query($insertTempSql);
			$data['code'] = 200;
			$data['msg']  = 'success';
			echo json_encode($data);
		}

		$delTempSql = "DELETE FROM $this->wet_temp WHERE tp_time <= now()-interval '3 D' AND tp_type = '$tp_type'";
		$this->db->query($delTempSql);

		$hashSql = "SELECT tp_hash, tp_to_hash FROM $this->wet_temp WHERE tp_type = '$tp_type' ORDER BY tp_time DESC";
		$query  = $this->db-> query($hashSql);

		foreach ($query-> getResult() as $row) {
			$tp_hash   = $row-> tp_hash;
			$tp_toHash = $row-> tp_to_hash;
			$this->decodeReward($tp_hash, $tp_toHash);
		}
	}

	public function decodeReward($hash, $to_hash)
	{//打赏数据处理
		$bsConfig  = $this->ConfigModel-> backendConfig();
		$getUrl	   = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$contents = file_get_contents($getUrl);
		$json 	   = (array) json_decode($contents, true);
		$sender_id = $json['sender_id'];
		$cuntnum   = 0;
		while ( !$sender_id && $cuntnum < 20) {
			@$contents = file_get_contents($getUrl);
			$json 	   = (array) json_decode($contents, true);
			$sender_id = $json['sender_id'];
			$cuntnum++;
			sleep(3);
		}

		if (empty($sender_id)) {
			$textFile   = fopen("log/reward/".date("Y-m-d").".txt", "a");
			$appendText = "error_aeknow_api--hash：{$hash}\r\nto_hash：{$to_hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return;
        }
		
		$recipient_id = $json['recipient_id'];
		$amount 	  = $json['amount'];
		$return_type  = $json['return_type'];
		$block_height = (int)$json['block_height'];
		$contract_id  = $json['contract_id'];

		if ($return_type == "revert") {
			$delete = "DELETE FROM wet_temp WHERE tp_hash = '$hash'";
			$this->db->query($delete);
			return;
		}

		$sql   = "SELECT sender_id FROM $this->wet_content WHERE hash = '$to_hash' LIMIT 1";
        $query = $this->db->query($sql);
		$row   = $query->getRow();
		$conID = $row-> sender_id;

		if ($row &&
			$contract_id == $bsConfig['WTTContractAddress'] &&
			$return_type == "ok"  &&
			$conID		 == $recipient_id
		) {
			$inData = [
				'hash'	       => $hash,
				'to_hash'      => $to_hash,
				'amount' 	   => $amount,
				'sender_id'    => $sender_id,
				'block_height' => $block_height
			];
			$this->db->table($this->wet_reward)->insert($inData);
			$upContSql = "UPDATE $this->wet_content SET reward_sum = (reward_sum + $amount) WHERE hash = '$to_hash'";
			$upUserSql = "UPDATE $this->wet_users SET reward_sum = (reward_sum + $amount) WHERE address = '$sender_id'";
			$this->db-> query($upContSql);
			$this->db-> query($upUserSql);
		} else {
			$textFile   = fopen("log/reward/".date("Y-m-d").".txt", "a");
			$appendText = "error--hash：{$hash}\r\nto_hash：{$to_hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			return;
		}
	}
}

