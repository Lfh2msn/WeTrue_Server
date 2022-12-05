<?php
namespace App\Controllers;

use App\Models\{
	StarModel,
	PraiseModel,
	SearchModel,
	RewardModel,
	DisposeModel,
	HashReadModel,
	ComplainModel
};

class Submit extends BaseController {

	public function praise(){
	//点赞
        $hash   = $this->request->getPost('hash');
        $type   = $this->request->getPost('type');
		if ($type == 'shTipid'){
			$isHash = DisposeModel::checkSuperheroTipid($hash);
		} else {
			$isHash = DisposeModel::checkAddress($hash);
		}

        if (
			$isHash 
			&& $type == 'topic' 
			|| $type == 'comment' 
			|| $type == 'reply' 
			|| $type == 'shTipid'
		) {
            echo (new PraiseModel())-> praise($hash, $type);
        } else {
			echo DisposeModel::wetJsonRt(406, 'error');
		}
    }

	public function hash($gHash=null){
	//发布hash
		$pHash   = $this->request->getPost('hash');
		$chainId = $_SERVER['HTTP_CHAIN_ID'] ?? 457;
		$hash    = $gHash ?? $pHash;
		$isHash  = DisposeModel::checkAddress($hash);
		if ($isHash){
            echo (new HashReadModel())-> split($hash, $chainId);
        } else {
			echo DisposeModel::wetJsonRt(406, 'error_hash');
		}
	}

	public function hashEvent(){
	//缓冲上链hash出库事件
        echo (new HashReadModel())-> hashEvent();
	}

	public function complain(){
	//投诉hash
		$hash  = $this->request->getPost('hash');
		$isHash = DisposeModel::checkAddress($hash);
		if ($isHash) {
            $data = (new ComplainModel())-> txHash($hash);
			echo $data;
        } else {
			echo DisposeModel::wetJsonRt(406, 'error_hash');
		}
	}

	public function search(){
	//搜索
		$page   = $this->request->getPost('page');
        $size   = $this->request->getPost('size');
        $offset = $this->request->getPost('offset');
		$type   = $this->request->getPost('type');
		$key    = $this->request->getPost('key');

		$opt  = [
			'type' => $type,
			'key'  => $key
		];
        if ($type && $key) {
            $data = (new SearchModel())-> search($page, $size, $offset, $opt);
		    echo $data;
		} else {
			echo DisposeModel::wetJsonRt(406, 'error');
		}
		
    }

}