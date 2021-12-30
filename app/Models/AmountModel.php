<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\UserModel;
use App\Models\ValidModel;

class AmountModel extends ComModel {
//Amount Model
	public function __construct(){
		parent::__construct();
		$this->tablename  = "wet_amount";
	}

	public function insertAmountUser($address, $amount)
	{//写入Amount VIP用户
		$data = [
			'address' => $address,
			'topic'   => $amount,
			'comment' => $amount,
			'reply'   => $amount
		];
		$this->db->table($this->tablename)->insert($data);
	}

}

