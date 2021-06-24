<?php
namespace App\Controllers;

use App\Models\ConfigModel;

class Config extends BaseController {

	public function info()
	{//获取前端配置
		$data['code'] = 200;
		$data['data'] = '';
		$reqAddress = $this->request->getPost('address');
		$aktAddress = $_SERVER['HTTP_AK_TOKEN'];
		$config  = (new ConfigModel())-> frontConfig($aktAddress ?? $reqAddress);
		if($config){
			$data['data'] = $config;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error';
		}
		echo json_encode($data);
    }

	public function nodes()
	{//获取节点列表
		$data['code'] = 200;
		$data['data'] = [];
		$config  = (new ConfigModel())-> nodesConfig();
		if($config){
			$data['data'] = $config;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error';
		}
		echo json_encode($data);
    }

	public function compiler()
	{//获取编译器列表
		$data['code'] = 200;
		$data['data'] = [];
		$config  = (new ConfigModel())-> compilerConfig();
		if($config){
			$data['data'] = $config;
			$data['msg']  = 'success';
		}else{
			$data['msg']  = 'error';
		}
		echo json_encode($data);
    }

}