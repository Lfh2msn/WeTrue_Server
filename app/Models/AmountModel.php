<?php 
namespace App\Models;

use App\Models\{
	ComModel
};

class AmountModel
{//Amount Model

	public static function insertAmountUser($address, $amount)
	{//写入Amount VIP用户
		$data = [
			'address' => $address,
			'topic'   => $amount,
			'comment' => $amount,
			'reply'   => $amount
		];
		ComModel::db()->table('wet_amount')->insert($data);
	}

}

