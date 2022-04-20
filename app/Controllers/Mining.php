<?php
namespace App\Controllers;

use App\Models\MiningModel;
use App\Models\ValidModel;

class Mining extends BaseController {

	public function openAccount()
	{//开通映射
        $hash 		   = $this->request->getPost('hash');
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_KEY'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isHash 	   = $this->DisposeModel-> checkAddress($hash);
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		if ($isHash && $isUserAddress) {
			(new MiningModel())-> openAccount($userAddress, $hash);
		} else {
			echo $this->DisposeModel-> wetJsonRt(406,'error_hash_OR_error_address');
		}
    }
	
	public function submitState()
	{//开通映射提交状态
        $reqAddress  = $this->request->getPost('userAddress');
		$aktAddress  = $_SERVER['HTTP_KEY'];
		$userAddress = $aktAddress ?? $reqAddress;
		$isAddress   = $this->DisposeModel-> checkAddress($userAddress);
		if ($isAddress) {
			$submitState  = (new ValidModel())-> isSubmitOpenState($userAddress);
			$isMapAccount = (new ValidModel())-> isMapAccount($userAddress);
			if ($submitState || $isMapAccount) {
				$data = $this->DisposeModel-> wetJsonRt(200,'error_repeat',true);
			} else {
				$data = $this->DisposeModel-> wetJsonRt(200,'success',false);
			}
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406,'error_address');
		}
		echo $data;
    }

	public function mapping()
	{//映射挖矿
		(int)$amount   = $this->request->getPost('amount');
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_KEY'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isMapAccount && is_numeric($amount) && $isUserAddress) {
			$data = (new MiningModel())-> inMapping($userAddress, $amount);
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406,'error_amount');
		}
		echo $data;
    }

	public function earning()
	{//领取收益
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_KEY'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isUserAddress && $isMapAccount) {
			$data = (new MiningModel())-> getEarning($userAddress);
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406,'error_address');
		}
		echo $data;
    }

	public function unMapping()
	{//解除映射
		$reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_KEY'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isMapAccount && $isUserAddress) {
			$data = (new MiningModel())-> unMapping($userAddress);
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406,'error_amount');
		}
		echo $data;
    }

	public function mapInfo()
	{//获取用户映射挖矿信息
        $reqAddress    = $this->request->getPost('userAddress');
		$aktAddress    = $_SERVER['HTTP_KEY'];
		$userAddress   = $aktAddress ?? $reqAddress;
		$isUserAddress = $this->DisposeModel-> checkAddress($userAddress);
		$isMapAccount  = (new ValidModel())-> isMapAccount($userAddress);
		if ($isUserAddress && $isMapAccount) {
			$userMiningInfo = (new MiningModel())-> checkMapping($userAddress);
			if ($userMiningInfo) {
				$data = $this->DisposeModel-> wetJsonRt(200, 'success', $userMiningInfo);
			} else {
				$data = $this->DisposeModel-> wetJsonRt(406, 'error_address');
			}
		} else {
			$data = $this->DisposeModel-> wetJsonRt(406, 'error_address');
		}
		echo $data;
    }

	public function top()
	{//映射榜单
		$data = (new MiningModel())-> topTen();
		echo $data;
		$this->cachePage(1);
    }

}