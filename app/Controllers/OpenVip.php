<?php
namespace App\Controllers;

use App\Models\ValidModel;
use App\Models\User\OpenVipModel;
use App\Models\Config\OpenVipConfig;

class OpenVip extends BaseController {

	public function state()
	{//提交开通VIP状态
		$address   = $_SERVER['HTTP_KEY'];
		$isAddress = $this->DisposeModel-> checkAddress($address);
		if ($isAddress) {
			$isOpenVipState = (new ValidModel())-> isOpenVipState($address);
			$isVipAddress   = (new ValidModel())-> isVipAddress($address);
			if ($isOpenVipState || $isVipAddress) {
				$data = $this->DisposeModel-> wetJsonRt(200,'error_repeat',true);
			} else {
				$data = $this->DisposeModel-> wetJsonRt(200,'success',false);
			}
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406,'error_address');
		}
		echo $data;
    }

	public function configInfo()
	{//获取前端配置
		$configInfo = (new OpenVipConfig())-> config();
		if($configInfo){
			$code = 200;
			$msg  = 'success';
			$data = $configInfo;
		}else{
			$code = 406;
			$msg  = 'error';
		}
		echo $this->DisposeModel-> wetJsonRt($code, $msg, $data);
    }

}