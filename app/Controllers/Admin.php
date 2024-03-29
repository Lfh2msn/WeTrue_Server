<?php
namespace App\Controllers;

use App\Models\{
	ComplainModel,
	DisposeModel,
	BloomModel,
	AirdropModel,
	MiningModel
};

class Admin extends BaseController
{//管理
	public function complainList()
	{//投诉列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$data   = (new ComplainModel())-> limit($page, $size, $offset);
		echo $data;
	}

	public function bloomList()
	{//屏蔽列表
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$data   = (new BloomModel())-> limit($page, $size, $offset);
		echo $data;
	}

	public function bloom()
	{//投诉hash屏蔽
		$hash   = $this->request->getPost('hash');
		$isHash = DisposeModel::checkAddress($hash);
        if ($isHash) {
            $data = (new BloomModel())-> bloomHash($hash);
			echo $data;
        } else {
			echo DisposeModel::wetJsonRt(406,'error_hash');
		}
	}

	public function unBloom()
	{//取消屏蔽
		$hash   = $this->request->getPost('hash');
		$isHash = DisposeModel::checkAddress($hash);
        if ($isHash) {
            $data = (new BloomModel())-> unBloom($hash);
			echo $data;
        } else {
			echo DisposeModel::wetJsonRt(406,'error_hash');
		}
	}

	public function airdropWTT()
	{//生成空投WTT文件
		$type = $this->request->getPost('type');
		if($type) {
			$opt = ['type' => $type];
		}
		$data = (new AirdropModel())-> airdropWTT($opt);
		echo $data;
	}

	public function openMapping()
	{//管理员开启映射挖矿
		$userAddress   = $this->request->getPost('userAddress');
        $isUserAddress = DisposeModel::checkAddress($userAddress);
		if ($isUserAddress) {
            $data = (new MiningModel())-> adminOpenMapping($userAddress);
			echo $data;
        } else {
			echo DisposeModel::wetJsonRt(406,'error');
		}
	}
}