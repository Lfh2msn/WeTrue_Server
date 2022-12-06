<?php
namespace App\Controllers;

use App\Models\{
	DisposeModel,
	MiningModel,
	ValidModel
};

class Mining extends BaseController {

	public function mapping()
	{//映射挖矿
		(int)$amount   = $this->request->getPost('amount');
		$userAddress   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$isVipAccount  = ValidModel::isVipAccount($userAddress);
		if ($isVipAccount && is_numeric($amount) && $isUserAddress) {
			$data = (new MiningModel())-> inMapping($userAddress, $amount);
		} else {
			$data = DisposeModel::wetJsonRt(406,'error_amount');
		}
		echo $data;
    }

	public function earning()
	{//领取收益
		$userAddress   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$isVipAccount  = ValidModel::isVipAccount($userAddress);
		if ($isUserAddress && $isVipAccount) {
			$data = (new MiningModel())-> getEarning($userAddress);
		} else {
			$data = DisposeModel::wetJsonRt(406,'error_address');
		}
		echo $data;
    }

	public function unMapping()
	{//解除映射
		$userAddress   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$isVipAccount  = ValidModel::isVipAccount($userAddress);
		if ($isVipAccount && $isUserAddress) {
			$data = (new MiningModel())-> unMapping($userAddress);
		} else {
			$data = DisposeModel::wetJsonRt(406,'error_amount');
		}
		echo $data;
    }

	public function mapInfo()
	{//获取用户映射挖矿信息
		$userAddress   = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
		$isUserAddress = DisposeModel::checkAddress($userAddress);
		$isVipAccount  = ValidModel::isVipAccount($userAddress);
		if ($isUserAddress && $isVipAccount) {
			$userMiningInfo = (new MiningModel())-> checkMapping($userAddress);
			if ($userMiningInfo) {
				$data = DisposeModel::wetJsonRt(200, 'success', $userMiningInfo);
			} else {
				$data = DisposeModel::wetJsonRt(406, 'error_address');
			}
		} else {
			$data = DisposeModel::wetJsonRt(406, 'error_address');
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