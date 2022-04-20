<?php namespace App\Models\Wecom;

use App\Models\ValidModel;
use App\Models\UserModel;

class CorpUserModel {
//企业微信绑定 Model

	public function __construct() {
		$this->db = \Config\Database::connect('default');
		$this->UserModel  = new UserModel();
		$this->ValidModel = new ValidModel();
		$this->wet_wecom_users = "wet_wecom_users";
    }

	public function bindUser($address, $corp_id, $user_id)
	{// 企业id绑定
		$this->UserModel-> userPut($address);
		$isWecomUserId = $this->ValidModel-> isWecomUserId($user_id);
		if ($isWecomUserId) {
			$this->db->table($this->wet_wecom_users)->where('wecom_user_id', $user_id)->update(['address' => $address]);
		} else {
			$insertData = [
				'address' 		=> $address,
				'wecom_corp_id' => $corp_id,
				'wecom_user_id' => $user_id
			];
			$this->db->table($this->wet_wecom_users)->insert($insertData);
		}
		return "绑定成功";
	}

	public function getUserId($address)
	{// 获取企业id
		$sql   = "SELECT wecom_user_id FROM $this->wet_wecom_users WHERE address = '$address' LIMIT 1";
		$query = $this->db->query($sql);
		$row   = $query->getRow();
		return $row ? $row->wecom_user_id : false;
	}

	public function getCountUser()
	{// 获取总绑定用户数
		$sql = "SELECT count(wecom_user_id) FROM $this->wet_wecom_users";
		$query = $this->db-> query($sql);
		$row   = $query-> getRow();
		return $row ? (int)$row->count : 0;
	}

}


