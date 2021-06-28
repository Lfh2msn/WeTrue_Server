<?php
namespace App\Controllers;

use App\Models\MiningModel;

class Mining extends BaseController {

	public function openAccount()
	{//开通映射
        $hash = $this->request->getPost('hash');
		$isHash = $this->DisposeModel-> checkAddress($hash);
		if($isHash){
			$data = (new MiningModel())-> openAccount($hash);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_hash';
			echo json_encode($data);
		}
    }

	public function mapping()
	{//映射挖矿
        (int)$amount   = $this->request->getPost('amount');
		$userAddress   = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new MiningModel())-> isMapAccount($userAddress);
		if($isMapAccount && is_numeric($amount) && $isUserAddress){
			$data = (new MiningModel())-> inMapping($userAddress, $amount);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
			echo json_encode($data);
		}
    }

	public function earning()
	{//领取收益
        $userAddress = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount = (new MiningModel())-> isMapAccount($userAddress);
		if($isUserAddress && $isMapAccount){
			$data = (new MiningModel())-> getEarning($userAddress);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_address';
			echo json_encode($data);
		}
    }

	public function unMapping()
	{//解除映射
		$userAddress   = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new MiningModel())-> isMapAccount($userAddress);
		if($isMapAccount && $isUserAddress){
			$data = (new MiningModel())-> unMapping($userAddress);
			echo $data;
		}else{
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
			echo json_encode($data);
		}
    }

	public function mapInfo()
	{//获取用户映射挖矿信息
        $userAddress = $this->request->getPost('userAddress');
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount = (new MiningModel())-> isMapAccount($userAddress);
		if($isUserAddress && $isMapAccount){
			$data['code']   = 200;
			$userMiningInfo = (new MiningModel())-> checkMapping($userAddress);
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

}