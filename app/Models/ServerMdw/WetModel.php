<?php 
namespace App\Models\ServerMdw;

use App\Models\ConfigModel;

class WetModel
{//WeTrue MDW交互

	public function getNewContentList()
	{//通知中间件，有新的消息
		$bsConfig = ConfigModel::backendConfig();
		$url  = $bsConfig['wetrueMdw'].'/Content/list';
		@$get = file_get_contents($url);
		$json = (array) json_decode($get, true);
		$num  = 0;
		while ( !$get && $num < 10) {
			@$get = file_get_contents($url);
			$json = (array) json_decode($get, true);
			$num++;
			sleep(6);
		}
	}
}

