<?php
namespace App\Controllers;

use App\Models\ConfigModel;

class Config extends BaseController {

	public function info()
	{//获取前端配置
		$data['code'] = 200;
		$data['data'] = '';
		$frontConfig  = (new ConfigModel())-> frontConfig();
		if($frontConfig){
			$data['data'] = $frontConfig;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error';
		}
		echo json_encode($data);
    }
}