<?php
namespace App\Controllers;

use App\Models\ConfigModel;
use App\Models\Config\{
	NodesConfig,
	CompilerConfig,
	BaseApiConfig,
	AeknowConfig,
	AeMdwConfig,
	WebClientConfig,
	RandomPortraitConfig
};

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

	public function url()
	{//获取各Url配置
		$data['baseApi']  = (new BaseApiConfig())-> urls();
		$data['nodes']    = (new NodesConfig())-> urls();
		$data['compiler'] = (new CompilerConfig())-> urls();
		$data['aeknow']   = (new AeknowConfig())-> urls();
		$data['aeMdw']    = (new AeMdwConfig())-> urls();
		$data['webClient'] = (new WebClientConfig())-> urls();
		
		$code = 200;
		$msg  = 'success';
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

	public function version()
	{//APP版本号检测
		$version = $this->request->getPost('version');
		$system  = $this->request->getPost('system');
		$list['version'] = $version;
		$list['system']  = $system ?? "Other";
		$data = (new ConfigModel())-> appVersionConfig($list);
		echo json_encode($data);
    }

	public function randomPortrait()
	{//随机头像配置
		$code = 200;
		$msg  = 'success';
		$data = (new RandomPortraitConfig())-> config();
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
	}
	

}