<?php
namespace App\Controllers;

use App\Models\GetModel;

class Aeknow extends BaseController {
//Aeknow API 模块
	public function api($type, ...$param)
	{	
		//Token 列表
		if ($type == "token") {
			echo json_encode( (NEW GetModel())-> getAeknowTokenList($param[0]) );
		}

		//AE 交易记录
		if ($type == "spendtx") {
			echo json_encode( (NEW GetModel())-> getAeknowSpendtx($param) );
		}

		//aex-9 Token交易记录
		if ($type == "tokentxs") {
			echo json_encode( (NEW GetModel())-> getAeknowTokentxs($param) );
		}

		//aex-9 Token信息查询
		if ($type == "mytoken") {
			echo json_encode( (NEW GetModel())-> getAeknowMyToken($param) );
		}

	}

}