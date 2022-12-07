<?php 
namespace App\Models\Wecom;

use App\Models\{
	ComModel,
	ValidModel,
	UserModel
};

class CorpUserModel
{//企业微信绑定 Model

	public function __construct() {
		$this->wet_wecom_users = "wet_wecom_users";
    }

	public function bindUser($address, $corp_id, $user_id)
	{// 企业id绑定
		UserModel::userPut($address);
		$isWecomUserId = ValidModel::isWecomUserId($user_id);
		if ($isWecomUserId) {
			ComModel::db()->table($this->wet_wecom_users)->where('wecom_user_id', $user_id)->update(['address' => $address]);
		} else {
			$insertData = [
				'address' 		=> $address,
				'wecom_corp_id' => $corp_id,
				'wecom_user_id' => $user_id
			];
			ComModel::db()->table($this->wet_wecom_users)->insert($insertData);
		}
		return "绑定成功";
	}

	public function saveCreateWallet($wallet, $user_id)
	{// 保存创建钱包
		/*
		{
			"mnemonic": "woman bicycle daughter rule polar night ecology ring game media avocado battle",
			"publicKey": "ak_FHUZQdTUVi3J5wgqh9tBcJbGGRxkG8Pn7v7ZsdFk24f51rGdx",
			"secretKey": "510421db7cdd7d16e9ab1a15030bb928bafbd3f0868313cecdca944cad8ce1f2206f067d0afa2234234dc9caec2a98f719f2b941e02a00ed0c323033331ea076"
		}
		*/
		try {
			$publicKey = $wallet['publicKey'];
			UserModel::userPut($publicKey);
			$updateData = [
				'wecom_mnemonic' => $wallet['mnemonic'],
				'wecom_address'  => $publicKey,
				'wecom_private'  => $wallet['secretKey']
			];
			ComModel::db()->table($this->wet_wecom_users)->where('wecom_user_id', $user_id)->update($updateData);
			return $publicKey;
		} catch (Exception $e) {
			return false;
		}
	}

	public function getUserId($address)
	{// 获取企业id
		$sql   = "SELECT wecom_user_id FROM $this->wet_wecom_users WHERE address = '$address' OR wecom_address = '$address' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? $row->wecom_user_id : false;
	}

	public function getCountUser()
	{// 获取总绑定用户数
		$sql = "SELECT count(wecom_user_id) FROM $this->wet_wecom_users";
		$query = ComModel::db()-> query($sql);
		$row   = $query-> getRow();
		return $row ? (int)$row->count : 0;
	}
		
	public function getWecomAddress($user_id)
	{// 获取企业钱包地址
		$sql   = "SELECT wecom_address FROM $this->wet_wecom_users WHERE wecom_user_id = '$user_id' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? $row->wecom_address : false;
	}

	public function getWecomPrivate($user_id)
	{// 获取企业钱包私钥
		$sql   = "SELECT wecom_private FROM $this->wet_wecom_users WHERE wecom_user_id = '$user_id' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? $row->wecom_private : false;
	}

	public function getWecomMnemonic($user_id)
	{// 获取企业钱包地址
		$sql   = "SELECT wecom_mnemonic FROM $this->wet_wecom_users WHERE wecom_user_id = '$user_id' LIMIT 1";
		$query = ComModel::db()->query($sql);
		$row   = $query->getRow();
		return $row ? $row->wecom_mnemonic : false;
	}

}


