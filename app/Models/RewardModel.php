<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\UserModel;
use App\Models\ConfigModel;
use App\Models\DisposeModel;
use App\Models\ValidModel;

class RewardModel extends Model {
//打赏Model

	public function __construct(){
        //parent::__construct();
		$this->db = \Config\Database::connect('default');
		$this->ConfigModel = new ConfigModel();
		$this->ValidModel  = new ValidModel();
		$this->wet_content = "wet_content";
		$this->wet_reward  = "wet_reward";
    }
	
	public function reward($hash, $to_hash)
	{//打赏
		$isRewardHash = $this->ValidModel-> isRewardHash($hash);
		if ( $isRewardHash ) {
			$data['code'] = 406;
			$data['msg']  = 'error_repeat';
			return json_encode($data);
		}

		$bsConfig  = $this->ConfigModel-> backendConfig();
		$getUrl	   = 'https://www.aeknow.org/api/contracttx/'.$hash;
		@$contents = file_get_contents($getUrl);
		if (empty($contents)) {

			$textFile   = fopen("reward_log/".date("Y-m-d").".txt", "a");
			$appendText = "hash：{$hash}\r\nto_hash:{$to_hash}\r\n\r\n";
			fwrite($textFile, $appendText);
			fclose($textFile);
			$data['code'] = 406;
			$data['msg']  = 'error_aeknow_api';
			return json_encode($data);
        }
		$json = (array) json_decode($contents, true);
		$sender_id 	  = $json['sender_id'];
		$recipient_id = $json['recipient_id'];
		$amount 	  = (int)$json['amount'];
		$return_type  = $json['return_type'];
		$block_height = (int)$json['block_height'];
		$contract_id  = $json['contract_id'];

		$sql   = "SELECT sender_id FROM wet_content WHERE hash = '$to_hash' LIMIT 1";
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
			$updateSql = "UPDATE $this->wet_content SET reward_sum = (reward_sum + $amount) WHERE hash = '$hash'";
			$this->db->query($updateSql);
			$data['code'] = 200;
			$data['msg']  = 'success';
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error';
		}

		return json_encode($data);
	}
}

