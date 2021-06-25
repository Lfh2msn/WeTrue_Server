<?php
namespace App\Controllers;

use App\Models\ConfigModel;
use App\Models\MiningModel;

class Mining extends BaseController {

	public function info()
	{//获取用户映射挖矿信息
        $userAddress = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount = (NEW MiningModel())-> isMapAccount($userAddress);
		if($isUserAddress && $isMapAccount){
			$data['code']   = 200;
			$userMiningInfo = (NEW MiningModel())-> checkMapping($userAddress);
			$data['data']   = '';
			if($userMiningInfo){
				$data['data'] = $userMiningInfo;
				$data['msg']  = 'success';
			}else{
				$data['msg']  = 'error_address';
			}
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_address';
		}
		echo json_encode($data);
    }

	public function mapping()
	{//映射挖矿
        $aeAmount = $this->request->getPost('aeAmount');
		if($aeAmount){
			$data = (NEW MiningModel())-> mapping($aeAmount);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
		}
    }

}