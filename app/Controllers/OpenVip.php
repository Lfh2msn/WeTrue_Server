<?php
namespace App\Controllers;

use App\Models\{
	DisposeModel,
	ValidModel
};
use App\Models\User\OpenVipModel;
use App\Models\Config\OpenVipConfig;

class OpenVip
{

	public function state()
	{//提交开通VIP状态
		$address   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isAddress = DisposeModel::checkAddress($address);
		if ($isAddress) {
			$isOpenVipState = ValidModel::isOpenVipState($address);
			$isVipAddress   = ValidModel::isVipAddress($address);
			if ($isOpenVipState || $isVipAddress) {
				$data = DisposeModel::wetJsonRt(200,'error_repeat',true);
			} else {
				$data = DisposeModel::wetJsonRt(200,'success',false);
			}
		} else {
			$data = DisposeModel::wetJsonRt(406,'error_address');
		}
		echo $data;
    }

	public function configInfo()
	{//获取前端配置
		$configInfo = OpenVipConfig::config();
		if($configInfo){
			$code = 200;
			$msg  = 'success';
			$data = $configInfo;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo DisposeModel::wetJsonRt($code, $msg, $data);
    }

}