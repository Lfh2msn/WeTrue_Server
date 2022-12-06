<?php
namespace App\Controllers;

use App\Models\{
	ConfigModel,
	DisposeModel
};
use App\Models\Config\{
	NodesConfig,
	CompilerConfig,
	BaseApiConfig,
	AeknowConfig,
	AeMdwConfig,
	WebClientConfig,
	RandomAvatarConfig
};

class Config extends BaseController
{

	public function info()
	{//获取前端配置
		$userAddress = isset($_SERVER['HTTP_KEY']);
		$config  = (new ConfigModel())-> frontConfig($userAddress);
		if($config){
			$code = 200;
			$msg  = 'success';
			$data = $config;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo DisposeModel::wetJsonRt($code, $msg, $data);
    }

	public function url()
	{//获取各Url配置
		$data['baseApi']  = BaseApiConfig::urls();
		$data['nodes']    = NodesConfig::urls();
		$data['compiler'] = CompilerConfig::urls();
		$data['aeknow']   = AeknowConfig::urls();
		$data['aeMdw']    = AeMdwConfig::urls();
		$data['webClient'] = WebClientConfig::urls();
		
		$code = 200;
		$msg  = 'success';
		echo DisposeModel::wetJsonRt($code, $msg, $data);
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
	{//随机头像配置 -- 即将废弃(2.5.0)(APP 2.9.5)
		$this-> randomAvatar();
	}
	
	public function randomAvatar()
	{//随机头像配置
		$code = 200;
		$msg  = 'success';
		$data = RandomAvatarConfig::config();
		echo DisposeModel::wetJsonRt($code, $msg, $data);
	}

}