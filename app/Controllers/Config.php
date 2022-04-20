<?php
namespace App\Controllers;

use App\Models\ConfigModel;

class Config extends BaseController {

	public function info()
	{//获取前端配置
		$userAddress  = $_SERVER['HTTP_KEY'];
		$config  = (new ConfigModel())-> frontConfig($userAddress);
		if($config){
			$code = 200;
			$msg  = 'success';
			$data = $config;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	public function nodes()
	{//获取AE节点列表
		$config  = (new ConfigModel())-> nodesConfig();
		$data['data'] = [];
		if($config){
			$code = 200;
			$msg  = 'success';
			$data = $config;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	public function compiler()
	{//获取编译器列表
		$config  = (new ConfigModel())-> compilerConfig();
		$data['data'] = [];
		if($config){
			$code = 200;
			$msg  = 'success';
			$data = $config;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	public function ipfsnodes()
	{//获取ipfs节点列表
		$config  = (new ConfigModel())-> ipfsNodeUrlConfig();
		$data['data'] = [];
		if($config){
			$code = 200;
			$msg  = 'success';
			$data = $config;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	public function version()
	{//APP版本号检测
		$appVer = $this->request->getPost('version');
		$data   = (new ConfigModel())-> appVersionConfig($appVer);
		echo json_encode($data);
    }
	

}