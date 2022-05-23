<?php
namespace App\Controllers;

use App\Models\Get\GetAeknowModel;

class Aeknow extends BaseController {
//Aeknow API 模块
	public function api($type, ...$param)
	{	
		//Token 列表
		if ($type == "token") {
			echo json_encode( (NEW GetAeknowModel())-> tokenList($param[0]) );
		}

		//Token 携带 Payload
		if ($type == "tokentx") {
			echo json_encode( (NEW GetAeknowModel())-> tokenPayloadTx($param[0]) );
		}

		//AE 交易记录
		if ($type == "spendtx") {
			echo json_encode( (NEW GetAeknowModel())-> spendTx($param) );
		}

		//aex-9 Token交易记录
		if ($type == "tokentxs") {
			echo json_encode( (NEW GetAeknowModel())-> tokenTxs($param) );
		}

		//aex-9 Token信息查询
		if ($type == "mytoken") {
			echo json_encode( (NEW GetAeknowModel())-> myToken($param) );
		}

	}

}