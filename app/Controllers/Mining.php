<?php
namespace App\Controllers;

use App\Models\MiningModel;
use App\Models\ValidModel;

class Mining extends BaseController {

	public function openAccount()
	{//开通映射
        $hash 		   = $this->request->getPost('hash');
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isHash 	   = $this->DisposeModel-> checkAddress($hash);
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if ($isHash && $isUserAddress) {
			(new MiningModel())-> openAccount($userAddress, $hash);
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_hash_OR_error_address';
			echo json_encode($data);
		}
    }
	
	public function submitState()
	{//开通映射提交状态
        $reqAddress  = $this->request->getPost('userAddress');
		$aktAddress  = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress = $aktAddress ?? $reqAddress;
		$isAddress   = $this->DisposeModel-> checkAddress($userAddress);
		if ($isAddress) {
			$data['code'] = 200;
			$submitState  = (new ValidModel())-> isSubmitOpenState($userAddress);
			$isMapAccount = (new ValidModel())-> isMapAccount($userAddress);
			if ($submitState || $isMapAccount) {
				$data['data'] = true;
				$data['msg']  = 'error_repeat';
			} else {
				$data['data'] = false;
				$data['msg']  = 'success';
			}
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_address';
		}
		echo json_encode($data);
    }

	public function mapping()
	{//映射挖矿
		(int)$amount   = $this->request->getPost('amount');
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isMapAccount && is_numeric($amount) && $isUserAddress) {
			$data = (new MiningModel())-> inMapping($userAddress, $amount);
			echo $data;
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
			echo json_encode($data);
		}
    }

	public function earning()
	{//领取收益
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isUserAddress && $isMapAccount) {
			$data = (new MiningModel())-> getEarning($userAddress);
			echo $data;
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_address';
			echo json_encode($data);
		}
    }

	public function unMapping()
	{//解除映射
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isMapAccount && $isUserAddress) {
			$data = (new MiningModel())-> unMapping($userAddress);
			echo $data;
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_amount';
			echo json_encode($data);
		}
    }

	public function mapInfo()
	{//获取用户映射挖矿信息
        $reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_AK_TOKEN'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isUserAddress && $isMapAccount) {
			$data['code']   = 200;
			$userMiningInfo = (new MiningModel())-> checkMapping($userAddress);
			$data['data']   = '';
			if ($userMiningInfo) {
				$data['data'] = $userMiningInfo;
				$data['msg']  = 'success';
			} else {
				$data['msg']  = 'error_address';
			}
		} else {
			$data['code'] = 406;
			$data['msg']  = 'error_address';
		}
		echo json_encode($data);
    }

}