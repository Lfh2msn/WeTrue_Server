<?php namespace App\Models;

use App\Models\ComModel;
use App\Models\ConfigModel;

class WttMdwModel extends ComModel {
//WeTrue MDW交互
	public function __construct(){
		parent::__construct();
		//$this->db = \Config\Database::connect('default');
		$this->ConfigModel  = new ConfigModel();
    }

	public function getNewContentList()
	{//通知中间件，有新的消息
		$bsConfig = $this->ConfigModel-> backendConfig();
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

