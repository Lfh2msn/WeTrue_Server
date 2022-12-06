<?php 
namespace App\Models;

use App\Models\{
	ComModel
};

class AmountModel
{//Amount Model
	public function __construct(){
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
		ComModel::db()->table($this->tablename)->insert($data);
	}

}

